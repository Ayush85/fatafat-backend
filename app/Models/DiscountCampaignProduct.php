<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountCampaignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'product_id', 'discount_type', 'discount_value'
    ];

    public function campaign()
    {
        return $this->belongsTo(DiscountCampaign::class, 'campaign_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
