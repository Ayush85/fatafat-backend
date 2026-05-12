<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasActivityLogs
{
    /**
     * Get all activity logs for the model
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'entity')
                    ->latest('created_at');
    }

    /**
     * Log a new activity
     */
    public function logActivity(
        string $action,
        string $label,
        ?string $description = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        array $meta = [],
        $actor = null
    ): ActivityLog {
        return $this->activities()->create([
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->id,

            'action' => $action,
            'label' => $label,
            'description' => $description,

            'old_status' => $oldStatus,
            'new_status' => $newStatus,

            'meta' => $meta,

            'created_at' => now(),
        ]);
    }

    /**
     * Log status change (helper method)
     */
    public function logStatusChange(
        string $newStatus,
        ?string $label = null,
        ?string $description = null,
        $actor = null
    ): ActivityLog {
        $oldStatus = $this->status ?? null;

        return $this->logActivity(
            action: 'status_changed',
            label: $label ?? "Status changed to {$newStatus}",
            description: $description,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            actor: $actor
        );
    }

    /**
     * Change status + log automatically
     */
    public function changeStatus(
        string $newStatus,
        ?string $label = null,
        ?string $description = null,
        $actor = null
    ): ActivityLog {
        $oldStatus = $this->status ?? null;

        // update model status
        $this->update([
            'status' => $newStatus,
        ]);

        // log activity
        return $this->logActivity(
            action: 'status_changed',
            label: $label ?? "Status changed to {$newStatus}",
            description: $description,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            actor: $actor
        );
    }

    /**
     * Get latest activity
     */
    public function latestActivity(): ?ActivityLog
    {
        return $this->activities()->first();
    }

    /**
     * Get activities grouped by date (useful for UI)
     */
    public function activitiesGroupedByDate()
    {
        return $this->activities()
            ->get()
            ->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            });
    }
}