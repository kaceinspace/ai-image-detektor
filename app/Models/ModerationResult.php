<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationResult extends Model
{
    protected $fillable = [
        'image_id',
        'adult_likelihood',
        'violence_likelihood',
        'racy_likelihood',
        'medical_likelihood',
        'spoof_likelihood',
        'raw_response',
        'processing_time_ms',
        'api_error',
        'analyzed_at',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'processing_time_ms' => 'integer',
        'analyzed_at' => 'datetime',
    ];

    /**
     * Get the image that owns this moderation result
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Check if any category is flagged as LIKELY or VERY_LIKELY
     */
    public function isFlagged(): bool
    {
        $dangerousLevels = ['LIKELY', 'VERY_LIKELY'];
        
        return in_array($this->adult_likelihood, $dangerousLevels) ||
               in_array($this->violence_likelihood, $dangerousLevels) ||
               $this->racy_likelihood === 'VERY_LIKELY';
    }

    /**
     * Get the reasons why this image was flagged
     */
    public function getFlaggedReasons(): array
    {
        $reasons = [];
        
        if (in_array($this->adult_likelihood, ['LIKELY', 'VERY_LIKELY'])) {
            $reasons[] = 'adult';
        }
        
        if (in_array($this->violence_likelihood, ['LIKELY', 'VERY_LIKELY'])) {
            $reasons[] = 'violence';
        }
        
        if ($this->racy_likelihood === 'VERY_LIKELY') {
            $reasons[] = 'racy';
        }
        
        return $reasons;
    }

    /**
     * Get CSS class for likelihood level (for badges)
     */
    public static function getLikelihoodClass(string $likelihood): string
    {
        return match($likelihood) {
            'VERY_LIKELY', 'LIKELY' => 'danger',
            'POSSIBLE' => 'warning',
            'UNLIKELY', 'VERY_UNLIKELY' => 'success',
            default => 'secondary',
        };
    }
}
