<?php

// Simple test of Google Vision client directly
require __DIR__ . '/vendor/autoload.php';

putenv('GOOGLE_APPLICATION_CREDENTIALS=/path/to/credentials.json'); // This might be the issue!

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;

try {
    // Initialize client
    $keyFilePath = 'G:/Candra/deteksi-ai/config/credentials/deteksi-foto-33f097f2b395.json';
    
    echo "Key file exists: " . (file_exists($keyFilePath) ? "YES" : "NO") . "\n";
    
    $client = new ImageAnnotatorClient([
        'credentials' => $keyFilePath,
    ]);
    
    echo "Client created successfully\n";
    
    // Test with an image
    $imagePath = 'G:/Candra/deteksi-ai/storage/app/private/uploads/images/2026/02/PnqYefbGQOSWiJkszprNK7LrIpBm7WTGjSxbYSpT.jpg';
    echo "Image exists: " . (file_exists($imagePath) ? "YES" : "NO") . "\n";
    
    $imageContent = file_get_contents($imagePath);
    $image = (new Image())->setContent($imageContent);
    
    $feature = (new Feature())->setType(Type::SAFE_SEARCH_DETECTION);
    $request = (new AnnotateImageRequest())
        ->setImage($image)
        ->setFeatures([$feature]);
    
    $batchRequest = (new BatchAnnotateImagesRequest())
        ->setRequests([$request]);
    
    echo "Calling API...\n";
    $response = $client->batchAnnotateImages($batchRequest);
    
    $annotations = $response->getResponses();
    echo "Got " .count($annotations) . " responses\n";
    
    if (count($annotations) > 0) {
        $safeSearch = $annotations[0]->getSafeSearchAnnotation();
        echo "Adult: " . $safeSearch->getAdult() . "\n";
        echo "Violence: " . $safeSearch->getViolence() . "\n";
    }
    
    $client->close();
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
