<?php

// Test Google Vision API directly
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Image;
use App\Services\ImageModerationService;

$image = Image::find(4); // Use image 4

echo "Testing Image ID: " . $image->id . "\n";
echo "File Path: " . $image->file_path . "\n\n";

$service = app(ImageModerationService::class);
$result = $service->processImage($image);

echo "Processing Result: " . ($result ? "SUCCESS" : "FAILED") . "\n\n";

$moderation = $image->fresh()->moderationResult;
if ($moderation) {
    echo "Moderation Result:\n";
    echo "  Adult: " . $moderation->adult_likelihood . "\n";
    echo "  Violence: " . $moderation->violence_likelihood . "\n";
    echo "  Racy: " . $moderation->racy_likelihood . "\n";
    echo "  Medical: " . $moderation->medical_likelihood . "\n";
    echo "  Spoof: " . $moderation->spoof_likelihood . "\n";
} else {
    echo "No moderation result found!\n";
}
