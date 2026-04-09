<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmiRequestBank extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'bank_id',
        'account_number',
        'branch',
    ];

    public function emiRequest()
    {
        return $this->belongsTo(EmiRequest::class, 'emi_request_id');
    }

    public function bank()
    {
        return $this->belongsTo(EmiBankModel::class, 'bank_id');
    }
}
