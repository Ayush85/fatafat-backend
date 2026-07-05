<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['module', 'settings'];

    protected $casts = ['settings' => 'array'];

    public static function getModule(string $module): ?array
    {
        return static::where('module', $module)->value('settings');
    }

    public static function getAllPublic(): array
    {
        return static::whereIn('module', static::PUBLIC_MODULES)
            ->get(['module', 'settings'])
            ->mapWithKeys(fn ($row) => [$row->module => $row->settings])
            ->all();
    }

    const PUBLIC_MODULES = ['core', 'contact', 'ecommerce', 'custom_scripts'];
}
