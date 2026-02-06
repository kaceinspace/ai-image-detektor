<?php

namespace App\Services;

use App\Models\Image;
use App\Models\ModerationResult;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class ImageModerationService
{
    protected GoogleVisionService $visionService;

    public function __construct(GoogleVisionService $visionService)
    {
        $this->visionService = $visionService;
    }

    /**
     * Process image through Google Vision API and save results
     * 
     * @param Image $image
     * @return bool Success status
     */
    public function processImage(Image $image): bool
    {
        // Update status to processing
        $image->update(['status' => 'processing']);

        // Get full path to image using Storage facade (respects configured disk root)
        $fullPath = \Illuminate\Support\Facades\Storage::path($image->file_path);

        // Analyze with Google Vision
        $result = $this->visionService->analyzeSafeSearch($fullPath);

        if (!$result['success']) {
            // Save error to moderation result
            ModerationResult::create([
                'image_id' => $image->id,
                'api_error' => $result['error'],
                'processing_time_ms' => $result['processing_time_ms'],
                'analyzed_at' => now(),
            ]);

            // Update image status back to pending
            $image->update(['status' => 'pending']);

            Log::error('Failed to process image', [
                'image_id' => $image->id,
                'error' => $result['error'],
            ]);

            return false;
        }

        // Create moderation result
        $moderationResult = ModerationResult::create([
            'image_id' => $image->id,
            'adult_likelihood' => $result['adult_likelihood'],
            'violence_likelihood' => $result['violence_likelihood'],
            'racy_likelihood' => $result['racy_likelihood'],
            'medical_likelihood' => $result['medical_likelihood'],
            'spoof_likelihood' => $result['spoof_likelihood'],
            'raw_response' => $result['raw_response'],
            'processing_time_ms' => $result['processing_time_ms'],
            'analyzed_at' => now(),
        ]);

        // Check if should auto-flag
        if ($this->shouldAutoFlag($moderationResult)) {
            $reasons = $moderationResult->getFlaggedReasons();
            
            $image->update([
                'is_flagged' => true,
                'flagged_reason' => implode(', ', $reasons),
                'status' => 'pending', // Keep as pending for admin review
            ]);

            // Log auto-flagging
            ActivityLog::createLog(
                action: 'auto_flagged',
                imageId: $image->id,
                description: 'Image automatically flagged by AI moderation',
                metadata: [
                    'reasons' => $reasons,
                    'adult_likelihood' => $result['adult_likelihood'],
                    'violence_likelihood' => $result['violence_likelihood'],
                    'racy_likelihood' => $result['racy_likelihood'],
                ]
            );
        } else {
            // Image is safe, set to pending for manual review
            $image->update(['status' => 'pending']);
        }

        // Update upload session counters
        $image->uploadSession->updateCounters();

        return true;
    }

    /**
     * Check if image should be auto-flagged based on thresholds
     * 
     * @param ModerationResult $moderationResult
     * @return bool
     */
    public function shouldAutoFlag(ModerationResult $moderationResult): bool
    {
        $dangerousLevels = ['LIKELY', 'VERY_LIKELY'];

        // Auto-flag if adult or violence is LIKELY or VERY_LIKELY
        if (in_array($moderationResult->adult_likelihood, $dangerousLevels)) {
            return true;
        }

        if (in_array($moderationResult->violence_likelihood, $dangerousLevels)) {
            return true;
        }

        // Auto-flag if racy is VERY_LIKELY (higher threshold)
        if ($moderationResult->racy_likelihood === 'VERY_LIKELY') {
            return true;
        }

        return false;
    }
}
