<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'host',
        'test_public_key',   // Test public API key
        'test_secret_key',   // Test private API key
        'live_public_key',   // Live public API key
        'live_secret_key',   // Live private API key
        'description',
        'vendor_id',
        'is_active',
        'mode', //test or live
    ];

    // Automatically generate public and private keys for both test and live environments
    protected static function booted()
    {
        static::creating(function ($apiKey) {
            // Generate test keys
            $apiKey->test_public_key = 'pk_test_' . Str::random(32);  // Test public key
            $apiKey->test_secret_key = 'sk_test_' . Str::random(64);  // Test secret key

            // Generate live keys
            $apiKey->live_public_key = 'pk_live_' . Str::random(32);  // Live public key
            $apiKey->live_secret_key = 'sk_live_' . Str::random(64);  // Live secret key
        });
    }
}