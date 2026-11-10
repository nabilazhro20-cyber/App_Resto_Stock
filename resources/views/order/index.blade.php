@extends('layouts.app')
@section('title', 'Order - Status Interaktif')
@section('content')
<style>
    [v-cloak] {
        display: none !important;
    }
</style>
<div id="orderIndexApp" class="container-fluid" v-cloak>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Daftar Order</h1>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-secondary" @click="loadOrders"
        :disabled="listState==='loading' || !!cancellingId">Refresh</button>
      <a href="{{ route('order.create') }}" class="btn btn-primary">New Order</a>
    </div>
  </div>
  <div v-if="notification" class="alert alert-success" role="status">@{{ notification }}</div>
  <div v-if="actionError" class="alert alert-danger" role="alert">@{{ actionError }}</div>
  <div v-if="listState==='loading'" class="alert alert-info" role="status">
    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
    Memuat Order dari server...
  </div>
  <div v-else-if="listState==='error'" class="alert alert-warning" role="alert">
    @{{ listError }}
    <button type="button" class="btn btn-sm btn-outline-dark ms-2" @click="loadOrders">Coba Lagi</button>
  </div>
  <div v-else-if="orders.length===0" class="alert alert-secondary">
    Belum ada Order tersimpan. Klik New Order untuk membuat transaksi pertama.
  </div>
  <div v-else class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light"><tr>
        <th>Kode</th>
        <th>Meja</th>
        <th>Jenis Item</th>
        <th>Total</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr></thead>
      <tbody>
        <tr v-for="o in orders" :key="o.id">
          <td>@{{ o.kode_order }}</td>
          <td>@{{ o.meja }}</td>
          <td>@{{ (o.details || []).length }}</td>
          <td>Rp @{{ rupiah(o.total) }}</td>
          <td><span class="badge" :class="o.status==='active'?'bg-primary':
            (o.status==='completed'?'bg-success':'bg-secondary')">@{{ o.status }}</span></td>
          <td>
            <template v-if="o.status==='active'">
              <a :href="`${ORDER_PAGE}/${o.id}/edit`" class="btn btn-sm btn-outline-primary me-1">Edit</a>
              <a :href="`${ORDER_PAGE}/${o.id}/checkout`" class="btn btn-sm btn-success me-1">Checkout</a>
              <button type="button" class="btn btn-sm btn-outline-danger"
                @click="cancelOrder(o)" :disabled="!!cancellingId || listState==='loading'">
                <span v-if="cancellingId===o.id" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                @{{ cancellingId===o.id ? 'Membatalkan...' : 'Cancel' }}
              </button>
            </template>
            <small v-else class="text-muted">Final (tidak dapat diubah)</small>
          </td>
        </tr>
      </tbody>
    </table>
  </div></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/order-ui.js') }}"></script>
<script>
const { createApp, ref, onMounted } = Vue;
createApp({ setup() {
  const { requestJson, rupiah, pesanError } = window.OrderUI;
  const API_ORDERS = @json(url('/api/orders'));
  const ORDER_PAGE = @json(url('/order'));
  const orders = ref([]), listState = ref('loading'), listError = ref('');
  const notification = ref(''), actionError = ref(''), cancellingId = ref(null);
 
  async function loadOrders() {
    listState.value = 'loading'; listError.value = '';
    try {
      const data = await requestJson(API_ORDERS);
      if (!Array.isArray(data)) throw new Error('Respons daftar Order harus berupa array.');
      orders.value = data; listState.value = 'ready';
    } catch (e) {
      listState.value = 'error'; listError.value = pesanError(e);
    }
  }
  async function cancelOrder(order) {
    if (cancellingId.value || order.status !== 'active') return;
    if (!window.confirm(`Batalkan ${order.kode_order}?`)) return;
    cancellingId.value = order.id; actionError.value = ''; notification.value = '';
    try {
      const result = await requestJson(`${API_ORDERS}/${order.id}/cancel`, { method: 'POST' });
      notification.value = result.message || 'Order berhasil dibatalkan.';
      await loadOrders(); // reload dari server, bukan mengubah status di client saja
    } catch (e) {
      actionError.value = pesanError(e);
    } finally { cancellingId.value = null; }
  }
  onMounted(loadOrders);
  return { orders, listState, listError, notification, actionError,
    cancellingId, ORDER_PAGE, rupiah, loadOrders, cancelOrder };
}}).mount('#orderIndexApp');
</script>
@endpush
