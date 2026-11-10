<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MenuController;
use App\Models\Product;
use App\Models\Menu;
use App\Http\Controllers\OrderController;
use App\Models\Order;
use app\Http\Controllers\OrderDetail;
use App\Models\MenuDetail;

Route::get('/', function () {
    $order = new Order();
    $total_penjualan = $order -> where('status', 'completed') -> sum('total');
    $list_order = $order -> where('status', 'completed') -> get();
    $total_profit = 0;
    foreach($list_order as $item_order){
        $order_details = DB::table('tbl_order_detail') -> where('order_id', $item_order->id) -> get();
        foreach($order_details as $item_detail){
            $qty = $item_detail->qty;
            $harga_jual = $item_detail->harga;
            $menu_detail = DB::table('tbl_menu_detail') -> where('id', $item_detail->menu_detail_id) -> first();
            $produk = DB::table('tbl_produk') -> find($menu_detail->id_product);
            $modal = $produk->modal;
            $profit = ($harga_jual - $modal) * $qty;
            $total_profit += $profit;
        }
    }
    return view('dashboard', [
        'jumlahProduk' => Product::count(),
        'jumlahMenu' => Menu::count(),
        'totalPenjualan' => $total_penjualan,
        'totalProfit' => $total_profit,
    ]);
})->name('dashboard');

Route::get('/product', [ProductController::class, 'index'])
    ->name('product.index');
Route::get('/product/add', [ProductController::class, 'create'])
    ->name('product.create');
Route::get('/product/{id}/edit', [ProductController::class, 'edit'])
    ->name('product.edit');

Route::get('/menu', [MenuController::class, 'index'])
    ->name('menu.index');
Route::get('/menu/add', [MenuController::class, 'create'])
    ->name('menu.create');
Route::get('/menu/{id}/edit', [MenuController::class, 'edit'])
    ->name('menu.edit');
Route::get('/order', [OrderController::class, 'index'])->name('order.index');
Route::get('/order/add', [OrderController::class, 'create'])->name('order.create');
Route::get('/order/{id}/edit', [OrderController::class, 'edit'])->name('order.edit');
Route::get('/order/{id}/checkout', [OrderController::class, 'checkoutPage'])
    ->name('order.checkout');

Route::get('/order', [OrderController::class, 'index'])->name('order.index');
Route::get('/order/add', [OrderController::class, 'create'])->name('order.create');
Route::get('/order/{id}/edit', [OrderController::class, 'edit'])->name('order.edit');
Route::get('/order/{id}/checkout', [OrderController::class, 'checkoutPage'])
    ->name('order.checkout');
Route::post('/order/reserve-stock', [OrderController::class, 'reserveStock'])
    ->name('order.reserveStock');
Route::post('/order/release-stock', [OrderController::class, 'releaseStock'])
    ->name('order.releaseStock');
Route::post('/order/expire-reservations', [OrderController::class, 'expireReservations'])
    ->name('order.expireReservations');


