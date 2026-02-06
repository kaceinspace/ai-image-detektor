<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\NsfwJsService;
use App\Models\Image;

echo "--- Testing NSFW.js Integration ---\n";

// Get an image
$image = Image::orderBy('id', 'desc')->first();
if (!$image) {
    echo "No images found in DB to test.\n";
    exit;
}

echo "Testing with Image ID: " . $image->id . "\n";
echo "Path: " . $image->file_path . "\n";

$fullPath = \Illuminate\Support\Facades\Storage::path($image->file_path);
echo "Full Path: " . $fullPath . "\n";

if (!file_exists($fullPath)) {
    echo "ERROR: File does not exist at path!\n";
    exit;
}

echo "\nInstantiating Service...\n";
$service = new NsfwJsService();

echo "Analyzing...\n";
$result = $service->analyze($fullPath);

echo "\n--- Result ---\n";
if ($result['success']) {
    echo "SUCCESS!\n";
    echo "Processing Time: " . $result['processing_time_ms'] . "ms\n";
    echo "Adult Likelihood: " . $result['adult_likelihood'] . "\n";
    echo "Racy Likelihood: " . $result['racy_likelihood'] . "\n";
    echo "Violence Likelihood: " . $result['violence_likelihood'] . "\n";
    echo "Spoof Likelihood: " . $result['spoof_likelihood'] . "\n";
    echo "\nRaw Scores:\n";
    print_r($result['raw_scores']);
} else {
    echo "FAILED!\n";
    echo "Error: " . $result['error'] . "\n";
}
