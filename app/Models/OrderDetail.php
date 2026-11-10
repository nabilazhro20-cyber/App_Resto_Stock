<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderDetail extends Model {
    protected $table = 'tbl_order_detail';
    protected $fillable = ['order_id','menu_detail_id','qty','harga','subtotal'];
    protected $casts = [
        'qty'=>'integer','harga'=>'integer','subtotal'=>'integer'
    ];
    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }
    public function menuDetail() {
        return $this->belongsTo(MenuDetail::class, 'menu_detail_id');
    }
}
