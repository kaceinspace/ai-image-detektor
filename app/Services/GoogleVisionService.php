<?php

namespace App\Services;

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image as VisionImage;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Illuminate\Support\Facades\Log;

class GoogleVisionService
{
    protected ?ImageAnnotatorClient $client = null;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('services.google_vision.enabled', false);
        
        if ($this->enabled) {
            try {
                $keyFilePath = config('services.google_vision.key_file');
                
                if (!file_exists($keyFilePath)) {
                    Log::warning('Google Vision API key file not found', ['path' => $keyFilePath]);
                    $this->enabled = false;
                    return;
                }

                $this->client = new ImageAnnotatorClient([
                    'credentials' => $keyFilePath,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to initialize Google Vision API client', [
                    'error' => $e->getMessage()
                ]);
                $this->enabled = false;
            }
        }
    }

    /**
     * Analyze image for safe search detection
     * 
     * @param string $imagePath Full path to the image file
     * @return array Result containing likelihood scores or error
     */
    public function analyzeSafeSearch(string $imagePath): array
    {
        $startTime = microtime(true);

        if (!$this->enabled || !$this->client) {
            return [
                'success' => false,
                'error' => 'Google Vision API is not enabled or initialized',
                'processing_time_ms' => 0,
            ];
        }

        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => 'Image file not found',
                'processing_time_ms' => 0,
            ];
        }

        try {
            // Read image content
            $imageContent = file_get_contents($imagePath);
            
            // Create Vision Image
            $image = (new VisionImage())->setContent($imageContent);
            
            // Create feature request for Safe Search Detection
            $feature = (new Feature())->setType(Type::SAFE_SEARCH_DETECTION);
            
            // Create annotation request
            $request = (new AnnotateImageRequest())
                ->setImage($image)
                ->setFeatures([$feature]);
            
            // Create batch request
            $batchRequest = (new BatchAnnotateImagesRequest())
                ->setRequests([$request]);
            
            // Perform safe search detection
            $response = $this->client->batchAnnotateImages($batchRequest);
            $annotations = $response->getResponses();
            
            if (count($annotations) === 0) {
                return [
                    'success' => false,
                    'error' => 'No annotations returned from Vision API',
                    'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000),
                ];
            }
            
            $safeSearch = $annotations[0]->getSafeSearchAnnotation();

            if (!$safeSearch) {
                return [
                    'success' => false,
                    'error' => 'No Safe Search annotation returned',
                    'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000),
                ];
            }

            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            return [
                'success' => true,
                'adult_likelihood' => $this->getLikelihoodString($safeSearch->getAdult()),
                'violence_likelihood' => $this->getLikelihoodString($safeSearch->getViolence()),
                'racy_likelihood' => $this->getLikelihoodString($safeSearch->getRacy()),
                'medical_likelihood' => $this->getLikelihoodString($safeSearch->getMedical()),
                'spoof_likelihood' => $this->getLikelihoodString($safeSearch->getSpoof()),
                'processing_time_ms' => $processingTime,
                'raw_response' => [
                    'adult' => $safeSearch->getAdult(),
                    'violence' => $safeSearch->getViolence(),
                    'racy' => $safeSearch->getRacy(),
                    'medical' => $safeSearch->getMedical(),
                    'spoof' => $safeSearch->getSpoof(),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Google Vision API error', [
                'error' => $e->getMessage(),
                'file' => $imagePath,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Convert Google Vision likelihood enum to string
     */
    protected function getLikelihoodString(int $likelihood): string
    {
        return match($likelihood) {
            0 => 'UNKNOWN',
            1 => 'VERY_UNLIKELY',
            2 => 'UNLIKELY',
            3 => 'POSSIBLE',
            4 => 'LIKELY',
            5 => 'VERY_LIKELY',
            default => 'UNKNOWN',
        };
    }

    /**
     * Check if the service is enabled and ready
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->client !== null;
    }

    /**
     * Close the client connection
     */
    public function __destruct()
    {
        if ($this->client) {
            $this->client->close();
        }
    }
}
