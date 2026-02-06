<?php

use App\Services\GoogleVisionService;
use Illuminate\Support\Facades\Log;

// Test if credentials file exists
$keyFile = config('services.google_vision.key_file');
echo "Credentials file: {$keyFile}\n";
echo "File exists: " . (file_exists($keyFile) ? 'YES' : 'NO') . "\n\n";

// Test Vision Service initialization
try {
    $visionService = app(GoogleVisionService::class);
    echo "GoogleVisionService initialized: " . ($visionService->isEnabled() ? 'ENABLED' : 'DISABLED') . "\n\n";
} catch (\Exception $e) {
    echo "ERROR initializing GoogleVisionService:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// Test with a sample image (if exists)
$testImagePath = storage_path('app/uploads/images');
$images = glob($testImagePath . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

if (count($images) > 0) {
    $testImage = $images[0];
    echo "Testing with image: " . basename($testImage) . "\n";
    
    try {
        $result = $visionService->analyzeSafeSearch($testImage);
        
        if ($result['success']) {
            echo "SUCCESS!\n";
            echo "Adult: {$result['adult_likelihood']}\n";
            echo "Violence: {$result['violence_likelihood']}\n";
            echo "Racy: {$result['racy_likelihood']}\n";
            echo "Medical: {$result['medical_likelihood']}\n";
            echo "Spoof: {$result['spoof_likelihood']}\n";
            echo "Processing time: {$result['processing_time_ms']}ms\n";
        } else {
            echo "FAILED: {$result['error']}\n";
        }
    } catch (\Exception $e) {
        echo "EXCEPTION:\n";
        echo $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "No test images found in {$testImagePath}\n";
}
