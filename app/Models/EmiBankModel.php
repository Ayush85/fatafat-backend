<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModel;

class EmiBankModel extends BaseModel
{
    protected $table = 'emi_banks';
    protected $casts = [
        'finance_amount_percentage' => 'array',        
    ];
    
}
