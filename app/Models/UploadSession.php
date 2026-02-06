<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadSession extends Model
{
    protected $fillable = [
        'session_token',
        'ip_address',
        'user_agent',
        'total_images',
        'approved_count',
        'rejected_count',
        'pending_count',
    ];

    protected $casts = [
        'total_images' => 'integer',
        'approved_count' => 'integer',
        'rejected_count' => 'integer',
        'pending_count' => 'integer',
    ];

    /**
     * Get all images in this upload session
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Increment total images counter
     */
    public function incrementTotalImages(): void
    {
        $this->increment('total_images');
    }

    /**
     * Update status counters based on current images
     */
    public function updateCounters(): void
    {
        $this->update([
            'approved_count' => $this->images()->where('status', 'approved')->count(),
            'rejected_count' => $this->images()->where('status', 'rejected')->count(),
            'pending_count' => $this->images()->where('status', 'pending')->count(),
        ]);
    }
}
