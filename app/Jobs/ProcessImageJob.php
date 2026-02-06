<?php

namespace App\Jobs;

use App\Models\Image;
use App\Services\ImageModerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Image $image
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageModerationService $moderationService): void
    {
        Log::info('Processing image', ['image_id' => $this->image->id]);

        try {
            $success = $moderationService->processImage($this->image);
            
            if ($success) {
                Log::info('Image processed successfully', ['image_id' => $this->image->id]);
            } else {
                Log::warning('Image processing failed', ['image_id' => $this->image->id]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing image', [
                'image_id' => $this->image->id,
                'error' => $e->getMessage(),
            ]);
            
            // Update image to pending if processing fails
            $this->image->update(['status' => 'pending']);
            
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessImageJob failed permanently', [
            'image_id' => $this->image->id,
            'error' => $exception->getMessage(),
        ]);

        // Set image back to pending so admin can manually review
        $this->image->update(['status' => 'pending']);
    }
}
