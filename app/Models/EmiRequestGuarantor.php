<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmiRequestGuarantor extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'gender',
        'marital_status',
        'citizenship_number',
    ];

    public function emiRequest()
    {
        return $this->belongsTo(EmiRequest::class, 'emi_request_id');
    }
}
