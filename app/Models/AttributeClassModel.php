<?php

declare(strict_types=1);

namespace App\Models;




use App\Models\BaseModel;
use App\Models\ProductAttributeModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeClassModel extends BaseModel
{
    protected $table = 'attribute_classes';

    protected $fillable = [
        'name',
    ];

    public function attributes() : HasMany{
        return $this->hasMany(ProductAttributeModel::class,'class_id');
    }
}
