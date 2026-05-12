<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'actor_type',
        'actor_id',
        'action',
        'label',
        'description',
        'old_status',
        'new_status',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function entity()
    {
        return $this->morphTo();
    }

    public function actor()
    {
        return $this->morphTo();
    }
}
