<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'start_date', 
        'end_date', 'is_active', 'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(DiscountCampaignProduct::class, 'campaign_id');
    }
}
