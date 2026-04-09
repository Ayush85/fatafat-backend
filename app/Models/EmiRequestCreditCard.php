<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmiRequestCreditCard extends Model
{

    use SoftDeletes;
    protected $fillable = [
        'card_number',
        'card_holder',
        'card_provider',
        'expiry_date',
        'credit_limit',
    ];

    public function emiRequest()
    {
        return $this->belongsTo(EmiRequest::class, 'emi_request_id');
    }

    public function cardProvider()
    {
        return $this->belongsTo(EmiBankModel::class, 'card_provider');
    }
}
