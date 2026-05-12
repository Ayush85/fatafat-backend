<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasActivityLogs;

    const STATUS_DRAFT = 0;

    const STATUS_PLACED = 1;

    const STATUS_CONFIRMED = 2;

    const STATUS_DISPATCHED = 3;

    const STATUS_COMPLETED = 4;

    const STATUS_CANCELED = 5;

    protected $fillable = [
        'user_id', 'cart_id', 'shipping_address_id', 'invoice_number',
        'status', 'discount_coupon', 'shipping_cost', 'cancel_reason',
        'discounts_total', 'order_total', 'total', 'payment_type',
    ];

    protected $appends = ['order_status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(UserShippingAddress::class, 'shipping_address_id');
    }

    public function getOrderStatusAttribute()
    {
        return match ($this->status) {
            self::STATUS_PLACED => 'Placed',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELED => 'Canceled',
            default => 'Draft',
        };
    }
}
