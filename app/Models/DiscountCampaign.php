<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_active_campaign'];

    public function getIsActiveCampaignAttribute()
    {
        $current_time = now();
        if ($this->start_date < $current_time && $this->end_date > $current_time) {
            return true;
        }
        return false;
    }

    public function products()
    {
        return $this->hasMany(DiscountCampaignProduct::class, 'campaign_id');
    }
}
