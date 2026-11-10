@extends('layouts.app')
@section('title', 'Checkout - Reactive UX')
@section('content')
<div id="checkoutApp" class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Checkout Order</h1>
    <a href="{{ route('order.index') }}" class="btn btn-outline-secondary">Daftar Order</a>
  </div>
  <div v-if="error" class="alert alert-danger" role="alert">@{{ error }}</div>
  <div v-if="success" class="alert alert-success" role="status">
    @{{ success }} <a href="{{ route('order.index') }}" class="alert-link ms-2">Kembali ke daftar</a>
  </div>
  <div v-if="loadState==='loading'" class="alert alert-info" role="status">
    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
    Memuat informasi pembayaran...
  </div>
  <div v-else-if="loadState==='error'" class="alert alert-warning" role="alert">
    Data Checkout tidak dapat dibuka.
    <button type="button" class="btn btn-sm btn-outline-dark" @click="load">Coba Lagi</button>
  </div>
  <div v-else class="card"><div class="card-body">
    <h2 class="h5">@{{ order.kode_order }} - Meja @{{ order.meja }}</h2>
    <p>Status: <strong>@{{ order.status }}</strong></p>
    <div v-if="!order.details.length" class="alert alert-secondary">Order tidak memiliki detail.</div>
    <div v-else class="table-responsive"><table class="table">
      <thead><tr><th>Product</th><th>Qty</th><th>Harga Snapshot</th><th>Subtotal</th></tr></thead>
      <tbody><tr v-for="d in order.details" :key="d.id">
        <td>@{{ d.menu_detail?.product?.name || d.menu_detail_id }}</td>
        <td>@{{ d.qty }}</td>
        <td>Rp @{{ rupiah(d.harga) }}</td>
        <td>Rp @{{ rupiah(d.subtotal) }}</td>
      </tr></tbody>
    </table></div>
    <div class="text-end fs-4 fw-bold mb-3">TOTAL: Rp @{{ rupiah(order.total) }}</div>
    <template v-if="order.status==='active' && !success">
      <label for="paid" class="form-label">Nominal Dibayar (tunai)</label>
      <input id="paid" type="number" min="0" step="1" v-model.number="paid"
        class="form-control mb-2" :disabled="saving">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="bayarPas" :disabled="saving">Uang pas</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="paid=50000" :disabled="saving">Rp 50.000</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="paid=100000" :disabled="saving">Rp 100.000</button>
      </div>
      <p class="fw-semibold" :class="difference>=0?'text-success':'text-danger'">
        @{{ difference>=0 ? 'Kembalian preview' : 'Uang kurang' }}:
        Rp @{{ rupiah(Math.abs(difference)) }}
      </p>
      <small class="text-muted d-block mb-3">Kembalian final dihitung ulang dan disimpan oleh backend.</small>
      <button type="button" class="btn btn-success" @click="submitCheckout"
        :disabled="saving || !canCheckout">
        <span v-if="saving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
        @{{ saving ? 'Memproses pembayaran...' : 'Proses Checkout' }}
      </button>
    </template>
    <div v-else class="alert alert-info">Order telah final; pembayaran tidak dapat diproses ulang.</div>
  </div></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/order-ui.js') }}"></script>
<script>
const { createApp, ref, computed, onMounted } = Vue;
createApp({ setup() {
  const { requestJson, rupiah, pesanError } = window.OrderUI;
  const orderId = @json($id), API_ORDERS = @json(url('/api/orders'));
  const order = ref({ details: [], total: 0, status: '' });
  const paid = ref(0), loadState = ref('loading'), saving = ref(false);
  const error = ref(''), success = ref('');
  const difference = computed(() => Number(paid.value || 0) - Number(order.value.total || 0));
  const canCheckout = computed(() => order.value.status === 'active' &&
    Number.isInteger(paid.value) && paid.value >= 0 && difference.value >= 0);
 
  async function load() {
    loadState.value = 'loading'; error.value = '';
    try {
      const data = await requestJson(`${API_ORDERS}/${orderId}`);
      if (!data || !Array.isArray(data.details)) throw new Error('Struktur data Checkout tidak valid.');
      order.value = data; loadState.value = 'ready';
    } catch (e) { loadState.value = 'error'; error.value = pesanError(e); }
  }
  function bayarPas() { paid.value = Number(order.value.total || 0); }
  async function submitCheckout() {
    if (saving.value || success.value) return;
    if (!canCheckout.value) { error.value = 'Pembayaran harus bilangan bulat dan mencukupi.'; return; }
    if (!window.confirm('Selesaikan pembayaran dan tutup Order?')) return;
    saving.value = true; error.value = '';
    try {
      const result = await requestJson(`${API_ORDERS}/${orderId}/checkout`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ paid_amount: paid.value })
      });
      // Status yang sah berasal dari respons server; detail tetap ada untuk review UI.
      order.value.status = result.data.status;
      order.value.paid_amount = result.data.paid_amount;
      order.value.change_amount = result.data.change_amount;
      success.value = `${result.message || 'Checkout berhasil.'} Kembalian: Rp ${rupiah(result.data.change_amount)}.`;
    } catch (e) { error.value = pesanError(e); }
    finally { saving.value = false; }
  }
  onMounted(load);
  return { order, paid, loadState, saving, error, success,
    difference, canCheckout, rupiah, load, bayarPas, submitCheckout };
}}).mount('#checkoutApp');
</script>
@endpush
