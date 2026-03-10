<?php

declare(strict_types=1);

namespace App\Models;


use App\Models\AttributeClassModel;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeModel extends BaseModel
{
    protected $table = 'product_attributes';

    protected $fillable = [
        'name', 'type', 'values', 'class_id', 'use_for_variant', 'use_in_filter',
    ];

    protected $casts = [
        'class_id' => 'integer',
        'use_for_variant' =>'boolean',
        'use_in_filter' =>'boolean',
        'values' =>'array',
    ];

    public function attribute() : BelongsTo{
        return $this->belongsTo(AttributeClassModel::class,'class_id');
    }
}
