<?php

// Test script to debug storage paths
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Image;
use Illuminate\Support\Facades\Storage;

$image = Image::orderBy('id', 'desc')->first();

echo "--- DEBUG STORAGE PATHS ---\n";
echo "Image ID: " . $image->id . "\n";
echo "File Path (DB): " . $image->file_path . "\n\n";

$phpPath = storage_path('app/' . $image->file_path);
echo "Using storage_path('app/xxx'): $phpPath\n";
echo "Exists? " . (file_exists($phpPath) ? "YES" : "NO") . "\n\n";

$storagePath = Storage::path($image->file_path);
echo "Using Storage::path(xxx): $storagePath\n";
echo "Exists? " . (file_exists($storagePath) ? "YES" : "NO") . "\n\n";

// Try to find the actual file
echo "--- SEARCHING FOR ACTUAL FILE ---\n";
$possiblePaths = [
    storage_path('app/' . $image->file_path),
    storage_path('app/private/' . $image->file_path),
    storage_path('app/public/' . $image->file_path),
    Storage::path($image->file_path),
];

foreach ($possiblePaths as $path) {
    echo "Checking: $path\n";
    if (file_exists($path)) {
        echo "  ✓ FOUND!\n";
    } else {
        echo "  ✗ Not found\n";
    }
}
