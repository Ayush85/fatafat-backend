<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    const STATUS_INITIATED = 'initiated';

    const STATUS_PENDING = 'pending';

    const STATUS_SUCCESS = 'success';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'order_id', 'gateway', 'transaction_uuid', 'gateway_transaction_id',
        'status', 'amount', 'currency', 'raw_request', 'raw_response',
    ];

    protected $casts = [
        'raw_request' => 'array',
        'raw_response' => 'array',
        'amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_FAILED, self::STATUS_CANCELED], true);
    }
}
