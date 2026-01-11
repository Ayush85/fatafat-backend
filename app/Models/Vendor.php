<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'description',
        'status',
        'avatar',
        'contact',
        'full_name',
        'contact_person_phone',
        'contact_person_address',
        'pan_no',
        'pan_document',
        'bank_cheque_copy'
    ];

    protected $appends = ['avatar_image', 'pan_document_image', 'bank_cheque_image'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getAvatarImageAttribute()
    {
        $baseUrl = config('filesystems.disks.public.url');
        return [
            "thumb" => $this->avatar ? "{$baseUrl}/uploads/images/vendors/{$this->id}/400x400{$this->avatar}" : "",
            "full" => $this->avatar ? "{$baseUrl}/uploads/images/vendors/{$this->id}/{$this->avatar}" : ""
        ];
    }

    public function getBankChequeImageAttribute()
    {
        $baseUrl = config('filesystems.disks.public.url');
        return $this->bank_cheque_copy ? "{$baseUrl}/uploads/vendors/{$this->id}/{$this->bank_cheque_copy}" : "";
    }

    public function getPanDocumentImageAttribute()
    {
        $baseUrl = config('filesystems.disks.public.url');
        return $this->pan_document ? "{$baseUrl}/uploads/vendors/{$this->id}/{$this->pan_document}" : "";
    }
}
