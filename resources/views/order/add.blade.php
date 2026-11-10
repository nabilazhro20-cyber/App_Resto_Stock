@extends('layouts.app')
@section('title', 'New Order - Reactive UX')
@section('content')
<div id="orderAddApp" class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">New Order</h1>
    <a href="{{ route('order.index') }}" class="btn btn-outline-secondary">Daftar Order</a>
  </div>
 
  <div v-if="success" class="alert alert-success" role="status">
    @{{ success }}
    <a href="{{ route('order.index') }}" class="alert-link ms-2">Lihat Order</a>
    <button type="button" class="btn btn-sm btn-outline-success ms-2" @click="windowReload">Order Baru</button>
  </div>
  <div v-if="formError" class="alert alert-danger" role="alert">@{{ formError }}</div>
 
  <div class="card mb-3"><div class="card-body">
    <h2 class="h5">1. Pilih meja dan menu</h2>
    <div v-if="menuState==='loading'" class="alert alert-info" role="status">
      <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
      Memuat daftar menu dari server...
    </div>
    <div v-else-if="menuState==='error'" class="alert alert-warning" role="alert">
      @{{ menuError }}
      <button type="button" class="btn btn-sm btn-outline-dark ms-2" @click="loadMenus">Coba Lagi</button>
    </div>
    <div v-else-if="menus.length===0" class="alert alert-secondary">
      Belum ada Menu Resto. Tambahkan data melalui halaman Menu terlebih dahulu.
    </div>
 
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label" for="meja">Meja</label>
        <input id="meja" class="form-control" placeholder="Contoh: 03"
          v-model.trim="meja" :disabled="!!savedOrder || saving">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="menuId">Menu Resto</label>
        <select id="menuId" class="form-select" v-model="menuId"
          @change="picked=''" :disabled="menuState!=='ready' || saving || !!savedOrder">
          <option value="">Pilih Menu</option>
          <option v-for="m in menus" :key="m.id" :value="m.id">@{{ m.nama }}</option>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="picked">Product dalam Menu</label>
        <select id="picked" class="form-select" v-model="picked"
          :disabled="!menuId || saving || !!savedOrder">
          <option value="">Pilih Product</option>
          <option
              v-for="d in options"
              :key="d.id"
              :value="d.id"
              :disabled="!d.product || d.product.stok <= 0">

              @{{ d.product?.name || 'Produk' }}
              - Rp @{{ rupiah(d.harga) }}
              (Stok: @{{ d.product?.stok ?? 0 }})
          </option>
        </select>
        <small v-if="menuId && !options.length" class="text-muted">Menu ini belum mempunyai item.</small>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="qty">Qty</label>
        <input id="qty" type="number" min="1" step="1" class="form-control"
          v-model.number="qty" :disabled="saving || !!savedOrder">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button type="button" class="btn btn-outline-primary w-100"
          @click="addItem" :disabled="!canAdd || !!savedOrder || reserving">@{{ reserving ? 'Memproses...' : 'Tambah Item' }}</button>
      </div>
      <div class="col-md-6 d-flex align-items-end text-muted">
        Subtotal pilihan: Rp @{{ rupiah(previewSubtotal) }}
      </div>
    </div>
  </div></div>
 
  <div class="card"><div class="card-body">
    <h2 class="h5">2. Review item sebelum disimpan</h2>
    <div v-if="items.length===0" class="alert alert-secondary">
      Keranjang Order masih kosong. Pilih Product dan tekan Tambah Item.
    </div>
    <div v-else class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Product</th><th>Harga Snapshot</th><th style="width:110px">Qty</th><th>Subtotal</th><th>Aksi</th></tr></thead>
        <tbody>
          <tr v-for="it in items" :key="it.menu_detail_id">
            <td>@{{ it.nama }}</td>
            <td>Rp @{{ rupiah(it.harga) }}</td>
            <td><input type="number"
                  min="1"
                  step="1"
                  class="form-control form-control-sm"
                  v-model.number="it.qty"
                  readonly
                  :aria-label="'Jumlah '+it.nama"></td>
            <td>Rp @{{ rupiah(subtotal(it)) }}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger"
              @click="removeItem(it.menu_detail_id)" :disabled="saving || !!savedOrder">Hapus</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-between align-items-center border-top pt-3">
      <span>@{{ jumlahJenis }} jenis product / @{{ jumlahUnit }} unit</span>
      <strong class="fs-5">Total: Rp @{{ rupiah(total) }}</strong>
    </div>
    <div v-if="items.length && !qtyValid" class="text-danger small mt-2">
      Semua quantity harus bilangan bulat minimal 1 sebelum Order disimpan.
    </div>
    <button type="button" class="btn btn-primary mt-3"
      @click="save" :disabled="!canSave || saving || !!savedOrder">
      <span v-if="saving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
      @{{ saving ? 'Mengirim Order...' : 'Simpan Order (ACTIVE)' }}
    </button>
  </div></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/order-ui.js') }}"></script>
<script>
const { createApp, ref, computed, onMounted } = Vue;
createApp({ setup() {
  const { requestJson, rupiah, pesanError } = window.OrderUI;
  const API_MENUS = @json(url('/api/menus'));
  const API_ORDERS = @json(url('/api/orders'));
  const API_RESERVE_STOCK = @json(route('order.reserveStock'));
  const API_RELEASE_STOCK = @json(route('order.releaseStock'));
  const menus = ref([]), menuState = ref('loading'), menuError = ref('');
  const meja = ref(''), menuId = ref(''), picked = ref(''), qty = ref(1);
  const items = ref([]), saving = ref(false), reserving = ref(false), formError = ref('');
  const savedOrder = ref(null), success = ref('');
 
  const options = computed(() => menus.value.find(m => m.id == menuId.value)?.details || []);
  const selected = computed(() => options.value.find(d => d.id == picked.value));
  const isPositiveInt = n => Number.isInteger(n) && n >= 1;
  const subtotal = it => isPositiveInt(it.qty) ? it.qty * Number(it.harga) : 0;
  const previewSubtotal = computed(() => selected.value && isPositiveInt(qty.value)
    ? Number(selected.value.harga) * qty.value : 0);
  const total = computed(() => items.value.reduce((sum, it) => sum + subtotal(it), 0));
  const jumlahJenis = computed(() => items.value.length);
  const jumlahUnit = computed(() => items.value.reduce((sum, it) =>
    sum + (isPositiveInt(it.qty) ? it.qty : 0), 0));
  const qtyValid = computed(() => items.value.every(it => isPositiveInt(it.qty)));
  const canAdd = computed(() => {
        const stok = Number(selected.value?.product?.stok ?? 0);
        return menuState.value === 'ready' &&
            !!selected.value &&
            isPositiveInt(qty.value) &&
            qty.value <= stok &&
            !saving.value &&
            !reserving.value;
    });
  const canSave = computed(() => meja.value.trim().length > 0 &&
    items.value.length > 0 && qtyValid.value && !saving.value);
 
  async function loadMenus() {
    menuState.value = 'loading'; menuError.value = '';
    try {
      const data = await requestJson(API_MENUS);
      if (!Array.isArray(data)) throw new Error('Format daftar menu tidak sesuai API.');
      menus.value = data; menuState.value = 'ready';
    } catch (e) {
      menuState.value = 'error'; menuError.value = pesanError(e);
    }
  }
  
  async function addItem() {
      if (!canAdd.value) return;
      const d = selected.value;
      const jumlah = qty.value;
      reserving.value = true;
      formError.value = '';
      try {
          const result = await requestJson(API_RESERVE_STOCK, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': @json(csrf_token())
              },
              body: JSON.stringify({
                  menu_detail_id: d.id,
                  qty: jumlah
              })
          });
          d.product.stok = result.remaining_stock;
          const existing = items.value.find(
              it => it.menu_detail_id == d.id
          );
          if (existing) {
              existing.qty += jumlah;
          } else {
              items.value.push({
                  menu_detail_id: d.id,
                  nama: d.product?.name || `Product ${d.id}`,
                  harga: Number(d.harga),
                  qty: jumlah
              });
          }
          picked.value = '';
          qty.value = 1;
          formError.value = '';
      } catch (e) {
          formError.value = pesanError(e);
      } finally {
          reserving.value = false;
      }
  }
  async function removeItem(id) {
      if (saving.value || reserving.value) return;

      const item = items.value.find(
          it => it.menu_detail_id == id
      );

      if (!item) return;

      try {
          // Meminta Laravel mengembalikan stok
          const result = await requestJson(API_RELEASE_STOCK, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': @json(csrf_token())
              },
              body: JSON.stringify({
                  menu_detail_id: id
              })
          });

          // Perbarui stok pada tampilan
          const detail = menus.value
              .flatMap(m => m.details || [])
              .find(d => d.id == id);

          if (detail && detail.product) {
              detail.product.stok = result.remaining_stock;
          }

          // Hapus item dari keranjang
          items.value = items.value.filter(
              it => it.menu_detail_id != id
          );

          formError.value = '';

      } catch (e) {
          formError.value = pesanError(e);
      }
  }
  async function save() {
    if (saving.value || savedOrder.value) return; // anti-double-submit
    if (!canSave.value) { formError.value = 'Isi meja, item, dan quantity yang valid.'; return; }
    saving.value = true; formError.value = '';
    try {
      // HANYA kirim identitas detail dan qty; harga/total dihitung backend P03.
      const payload = { meja: meja.value, items: items.value.map(it => ({
        menu_detail_id: it.menu_detail_id, qty: it.qty })) };
      const result = await requestJson(API_ORDERS, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      savedOrder.value = result.data;
      success.value = `${result.message || 'Order berhasil disimpan.'} Kode: ${result.data?.kode_order || '-'}`;
    } catch (e) {
      formError.value = pesanError(e); // 422 dipresentasikan tanpa menghapus isi form
    } finally { saving.value = false; }
  }
  function windowReload() { window.location.reload(); }
  onMounted(loadMenus);
  return { menus, menuState, menuError, meja, menuId, picked, qty, items,
    saving, formError, success, savedOrder, options, total, previewSubtotal,
    jumlahJenis, jumlahUnit, qtyValid, canAdd, canSave, rupiah, subtotal,
    loadMenus, addItem, removeItem, save, reserving, windowReload };
}}).mount('#orderAddApp');
</script>
@endpush
