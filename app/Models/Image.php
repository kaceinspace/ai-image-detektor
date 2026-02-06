<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'upload_session_id',
        'filename',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'reviewed_at',
        'reviewed_by',
        'review_note',
        'is_flagged',
        'flagged_reason',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_flagged' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the upload session that owns this image
     */
    public function uploadSession(): BelongsTo
    {
        return $this->belongsTo(UploadSession::class);
    }

    /**
     * Get the moderation result for this image
     */
    public function moderationResult(): HasOne
    {
        return $this->hasOne(ModerationResult::class);
    }

    /**
     * Get the user who reviewed this image
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get all activity logs for this image
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Scope to filter only flagged images
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending images
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get full URL to the image
     */
    public function getUrlAttribute(): string
    {
        return route('image.serve', ['image' => $this->id]);
    }

    /**
     * Get human-readable file size
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
