<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    protected $table = 'tbl_order';
    protected $fillable = [
        'kode_order','meja','active_meja_key','status','total',
        'paid_amount','change_amount','checked_out_at','cancelled_at'
    ];
    protected $casts = [
        'total'=>'integer','paid_amount'=>'integer',
        'change_amount'=>'integer','checked_out_at'=>'datetime',
        'cancelled_at'=>'datetime'
    ];
    public function details() {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}
