<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    use HasActivityLogs, HasFactory;

    protected $table = 'orders';

    const STATUS_DRAFT = 0;

    const STATUS_PLACED = 1;

    const STATUS_CONFIRMED = 2;

    const STATUS_DISPATCHED = 3;

    const STATUS_COMPLETED = 4;

    const STATUS_CANCELED = 5;

    const STATUS_DELIVERED = 6;

    protected $guarded = [];

    protected $casts = [
        'is_pre_order' => 'boolean',
        'deposit_amount' => 'decimal:2',
    ];

    protected $appends = ['order_status'];

    public function getMorphClass()
    {
        return $this->getTable(); // returns 'orders'
    }

    protected static function booted()
    {
        static::created(function ($order) {
            $order->order_no = 'FTS-ORD-'
                .now()->format('Ymd')
                .'-'
                .sprintf('%06d', $order->id);

            $order->save();
        });
    }

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
            self::STATUS_DELIVERED => 'Delivered',
            default => 'Draft',
        };
    }
}
