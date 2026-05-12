<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_number',
        'status',
        'avatar',
        'address',
        'date_of_birth',
        'social_platform',
        'social_id',
        'institute_name',
    ];
     public function getMorphClass()
    {
        return $this->getTable(); // returns 'orders'
    }

    protected $appends = ['avatar_image'];

    public function getAvatarImageAttribute()
    {
        $baseUrl = config('filesystems.disks.public.url'); // https://fatafatsewa.com/storage

        return [
            'thumb' => $this->avatar ? "{$baseUrl}/uploads/avatars/users/{$this->id}/400x400{$this->avatar}" : '',
            'full' => $this->avatar ? "{$baseUrl}/uploads/avatars/users/{$this->id}/{$this->avatar}" : '',
        ];
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class)->where('is_processed', 0);
    }

    public function shippingAddresses()
    {
        return $this->hasMany(UserShippingAddress::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }
}
