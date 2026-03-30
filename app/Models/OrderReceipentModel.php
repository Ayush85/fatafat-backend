<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReceipentModel extends Model
{
    use HasFactory;

    protected $table = 'order_receipents';

   
    protected $guarded = [];

    
    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

}
