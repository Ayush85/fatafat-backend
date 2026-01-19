<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmiRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'email', 'contact_number', 'address',
        'dob_ad', 'dob_bs', 'gender', 'credit_card', 'bank',
        'length_of_employment', 'monthly_income', 'no_of_dependents',
        'occupation', 'residental_status', 'vehicle', 'emi_mode',
        'down_payment', 'finance_amount', 'emi_per_month',
        'product_attributes', 'salary_certificate', 'citizenship',
        'photo', 'product_id', 'user_id', 'product_price',
        'status', 'bank_statement', 'card_holder_name',
        'card_number', 'card_expiry_date'
    ];

    protected $casts = [
        'dob_ad' => 'date',
        'dob_bs' => 'date',
        'product_attributes' => 'array',
        'monthly_income' => 'double',
        'down_payment' => 'double',
        'finance_amount' => 'double',
        'emi_per_month' => 'double',
        'product_price' => 'double',
    ];
