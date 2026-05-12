<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class FileModel extends Model
{
    protected $table = 'files';
    protected $guarded = [];

    protected $appends = ['url'];
    protected $casts = [
        'meta' => 'array',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(FileUsageModel::class, 'file_id');
    }

    public function getUrlAttribute(): ?string
    {
        $path = $this->file_path;
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = trim($path);
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $relativePath = ltrim($path, '/');
        $disk = is_string($this->disk) && trim($this->disk) !== '' ? trim($this->disk) : (string) config('filesystems.default');
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);

        return $storage->url($relativePath);
    }
}
