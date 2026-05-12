<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmiRequest extends Model
{
    use HasFactory, HasActivityLogs;

    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'address',
        'dob_ad',
        'dob_bs',
        'gender',
        'credit_card',
        'bank',
        'length_of_employment',
        'monthly_income',
        'no_of_dependents',
        'occupation',
        'residental_status',
        'vehicle',
        'emi_mode',
        'down_payment',
        'finance_amount',
        'emi_per_month',
        'product_attributes',
        'salary_certificate',
        'citizenship',
        'photo',
        'product_id',
        'user_id',
        'product_price',
        'status',
        'bank_statement',
        'card_holder_name',
        'card_number',
        'card_expiry_date',
        // New doc fields for differentiated EMI flows
        'citizenship_front',
        'citizenship_back',
        'pp_photo',
        'signature',
        'agreed_terms',

        'emi_type',
        'interest_rate',
        'product_variant',
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
        'credit_card' => 'array',
    ];

    public const STATUS_PENDING = 0;

    public const STATUS_PROCESSING = 1;

    public const STATUS_APPROVED = 2;

    public const STATUS_FINISHED = 3;

    public const STATUS_CANCELLED = 4;

     public function getMorphClass()
    {
        return $this->getTable(); // returns 'orders'
    }
    
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_FINISHED => 'Finished',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'emi_requests')
            ->withPivot(['title'])
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creditCard()
    {
        return $this->hasOne(EmiRequestCreditCard::class);
    }
    public function guarantor()
    {
        return $this->hasOne(EmiRequestGuarantor::class);
    }

    public function preferredBank()
    {
        return $this->hasOne(EmiRequestBank::class);
    }
}
