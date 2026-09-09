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

    // The full PAN must never leave the server in an API response — only
    // card_number_masked (last 4 digits) does. Any endpoint that serializes
    // this model (or the EmiRequest.creditCard relation) is covered by this,
    // not just the one that leaked it.
    protected $hidden = [
        'card_number',
    ];

    protected $appends = [
        'card_number_masked',
    ];

    public function getCardNumberMaskedAttribute(): ?string
    {
        if (! $this->card_number) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->card_number);
        $lastFour = substr($digits, -4);

        return '**** **** **** '.$lastFour;
    }

    public function emiRequest()
    {
        return $this->belongsTo(EmiRequest::class, 'emi_request_id');
    }

    public function cardProvider()
    {
        return $this->belongsTo(EmiBankModel::class, 'card_provider');
    }
}
