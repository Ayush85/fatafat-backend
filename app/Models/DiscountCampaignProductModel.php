<?php

namespace App\Models;


use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class DiscountCampaignProductModel extends Model
{
    protected $table = 'discount_campaign_products';
    protected $fillable = [
      'product_id', 'discount_type', 'discount_value', 'campaign_id'
    ];

    protected $appends = ['discount_type_label'];

    public function getDiscountTypeLabelAttribute()
    {
        return $this->discount_type == 1 ? 'fixed' : 'percentage';
    }

    public function product()
    {
        return $this->hasOne(ProductModel::class, 'id', 'product_id');
    }

    public function campaign()
    {
        return $this->belongsTo(DiscountCampaignModel::class, 'campaign_id');
    }
}
