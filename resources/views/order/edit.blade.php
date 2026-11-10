@extends('layouts.app')
@section('title', 'Edit Order - Reactive UX')
@section('content')
<div id="orderEditApp" class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Edit Order</h1>
    <a href="{{ route('order.index') }}" class="btn btn-outline-secondary">Daftar Order</a>
  </div>
  <div v-if="error" class="alert alert-danger" role="alert">@{{ error }}</div>
  <div v-if="success" class="alert alert-success" role="status">
    @{{ success }} <a href="{{ route('order.index') }}" class="alert-link ms-2">Lihat Order</a>
  </div>
  <div v-if="loadState==='loading'" class="alert alert-info" role="status">
    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
    Memuat data Order dan Menu...
  </div>
  <div v-else-if="loadState==='error'" class="alert alert-warning" role="alert">
    Gagal membuka editor. <button type="button" class="btn btn-sm btn-outline-dark" @click="load">Coba Lagi</button>
  </div>
  <div v-else-if="status!=='active'" class="alert alert-warning">
    Order sudah final. Hanya Order ACTIVE yang boleh diubah.
  </div>
  <template v-else>
    <div class="card mb-3"><div class="card-body row g-3">
      <div class="col-md-3">
        <label class="form-label" for="editMeja">Meja</label>
        <input id="editMeja" class="form-control" v-model.trim="meja" :disabled="saving || !!success">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="editMenu">Menu Resto</label>
        <select id="editMenu" class="form-select" v-model="menuId" @change="picked=''" :disabled="saving || !!success">
          <option value="">Pilih Menu</option>
          <option v-for="m in menus" :key="m.id" :value="m.id">@{{ m.nama }}</option>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="editProduct">Product dalam Menu</label>
        <select id="editProduct" class="form-select" v-model="picked" :disabled="!menuId || saving || !!success">
          <option value="">Pilih Product</option>
          <option v-for="d in options" :key="d.id" :value="d.id">
            @{{ d.product?.name || 'Product' }} - Rp @{{ rupiah(d.harga) }}
          </option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="editQty">Qty tambahan</label>
        <input id="editQty" type="number" min="1" step="1" class="form-control" v-model.number="qty"
          :disabled="saving || !!success">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button type="button" class="btn btn-outline-primary w-100" @click="addItem"
          :disabled="!canAdd || !!success">Tambah Item</button>
      </div>
      <div class="col-md-6 d-flex align-items-end text-muted">
        Harga untuk item yang sudah ada tetap memakai snapshot transaksi.
      </div>
    </div></div>
    <div class="card"><div class="card-body">
      <div v-if="items.length===0" class="alert alert-secondary">Belum ada item dalam Order.</div>
      <div v-else class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Product</th><th>Qty</th><th>Harga Snapshot</th><th>Subtotal</th><th>Aksi</th></tr></thead>
        <tbody><tr v-for="it in items" :key="it.menu_detail_id">
          <td>@{{ it.nama }}</td>
          <td style="width:110px"><input type="number" min="1" step="1"
            class="form-control form-control-sm" v-model.number="it.qty" :disabled="saving || !!success"></td>
          <td>Rp @{{ rupiah(it.harga) }}</td>
          <td>Rp @{{ rupiah(subtotal(it)) }}</td>
          <td><button type="button" class="btn btn-sm btn-outline-danger"
            @click="removeItem(it.menu_detail_id)" :disabled="saving || !!success">Hapus</button></td>
        </tr></tbody>
      </table></div>
      <div class="text-end fw-bold fs-5">Total preview: Rp @{{ rupiah(total) }}</div>
      <small class="text-muted d-block">Total dan harga resmi tetap dihitung oleh backend saat menyimpan.</small>
      <div v-if="!validItems && items.length" class="text-danger small mt-2">Qty harus bilangan bulat minimal 1.</div>
      <button type="button" class="btn btn-primary mt-3" @click="save"
        :disabled="!canSave || saving || !!success">
        <span v-if="saving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
        @{{ saving ? 'Memperbarui...' : 'Simpan Perubahan' }}
      </button>
    </div></div>
  </template>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/order-ui.js') }}"></script>
<script>
const { createApp, ref, computed, onMounted } = Vue;
createApp({ setup() {
  const { requestJson, rupiah, pesanError } = window.OrderUI;
  const orderId = @json($id);
  const API_ORDERS = @json(url('/api/orders'));
  const API_MENUS = @json(url('/api/menus'));
  const menus = ref([]), meja = ref(''), status = ref(''), items = ref([]);
  const menuId = ref(''), picked = ref(''), qty = ref(1);
  const loadState = ref('loading'), saving = ref(false), error = ref(''), success = ref('');
  const options = computed(() => menus.value.find(m => m.id == menuId.value)?.details || []);
  const selected = computed(() => options.value.find(d => d.id == picked.value));
  const isPositiveInt = n => Number.isInteger(n) && n >= 1;
  const subtotal = it => isPositiveInt(it.qty) ? it.qty * Number(it.harga) : 0;
  const total = computed(() => items.value.reduce((sum, it) => sum + subtotal(it), 0));
  const validItems = computed(() => items.value.every(it => isPositiveInt(it.qty)));
  const canAdd = computed(() => !!selected.value && isPositiveInt(qty.value) && !saving.value);
  const canSave = computed(() => status.value === 'active' && meja.value.trim() &&
    items.value.length > 0 && validItems.value && !saving.value);
 
  async function load() {
    loadState.value = 'loading'; error.value = '';
    try {
      const [menuResponse, orderResponse] = await Promise.all([
        requestJson(API_MENUS), requestJson(`${API_ORDERS}/${orderId}`)
      ]);
      if (!Array.isArray(menuResponse) || !Array.isArray(orderResponse.details))
        throw new Error('Struktur data edit tidak sesuai API.');
      menus.value = menuResponse;
      meja.value = orderResponse.meja; status.value = orderResponse.status;
      items.value = orderResponse.details.map(d => ({
        menu_detail_id: d.menu_detail_id,
        nama: d.menu_detail?.product?.name || `Item ${d.menu_detail_id}`,
        harga: Number(d.harga), // harga SNAPSHOT dari OrderDetail, bukan MenuDetail terbaru
        qty: Number(d.qty)
      }));
      loadState.value = 'ready';
    } catch (e) { loadState.value = 'error'; error.value = pesanError(e); }
  }
  function addItem() {
    if (!canAdd.value) return;
    const d = selected.value;
    const previous = items.value.find(it => it.menu_detail_id == d.id);
    if (previous) previous.qty += qty.value; // pertahankan harga snapshot existing
    else items.value.push({ menu_detail_id: d.id,
      nama: d.product?.name || `Product ${d.id}`, harga: Number(d.harga), qty: qty.value });
    picked.value = ''; qty.value = 1; error.value = '';
  }
  function removeItem(id) { items.value = items.value.filter(it => it.menu_detail_id != id); }
  async function save() {
    if (saving.value || success.value) return;
    if (!canSave.value) { error.value = 'Meja dan item/qty harus valid.'; return; }
    saving.value = true; error.value = '';
    try {
      const data = await requestJson(`${API_ORDERS}/${orderId}`, {
        method: 'PUT', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ meja: meja.value, items: items.value.map(it => ({
          menu_detail_id: it.menu_detail_id, qty: it.qty })) })
      });
      success.value = data.message || 'Perubahan Order berhasil disimpan.';
    } catch (e) { error.value = pesanError(e); }
    finally { saving.value = false; }
  }
  onMounted(load);
  return { menus, meja, status, items, menuId, picked, qty, options,
    loadState, saving, error, success, canAdd, canSave, total, validItems,
    subtotal, rupiah, load, addItem, removeItem, save };
}}).mount('#orderEditApp');
</script>
@endpush
