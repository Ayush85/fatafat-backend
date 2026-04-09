<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmiRequestGuarantor extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'marriage_status',
        'citizenship_number',
    ];

    public function emiRequest()
    {
        return $this->belongsTo(EmiRequest::class, 'emi_request_id');
    }

      public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'emi_request_guarantors')
            ->withPivot(['title'])
            ->withTimestamps();
    }
}
