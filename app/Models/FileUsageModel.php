<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FileUsageModel extends Pivot
{
    protected $table = 'file_usages';

    protected $casts = [
        'meta' => 'array',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(FileModel::class, 'file_id');
    }
}
