<?php
namespace App\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCampaignModel extends Model
{
    use SoftDeletes;
    protected $table = 'discount_campaigns';
    protected $fillable = [
      "title", "slug", "start_date", "end_date",'is_published'
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected $appends = ['is_active'];
    public function getIsActiveAttribute()
    {
        $current_time = Carbon::now();
        if ($this->start_date < $current_time && $this->end_date > $current_time) {
            return true;
        }
        return false;
    }

    public function products(){
        return $this->hasMany(DiscountCampaignProductModel::class, 'campaign_id', 'id');
    }

    public function files(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->using(FileUsageModel::class)
            ->wherePivot('usage_type', 'campaigns')
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps()
            ->orderByPivot('id', 'desc');
    }

    public function defaultFile(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->using(FileUsageModel::class)
            ->wherePivot('usage_type', 'campaigns')
            ->wherePivot('meta->is_default', true)
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->limit(1);
    }
}
