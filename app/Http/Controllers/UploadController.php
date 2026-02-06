<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use App\Models\Image;
use App\Models\UploadSession;
use App\Jobs\ProcessImageJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Show upload form
     */
    public function index()
    {
        return view('upload');
    }

    /**
     * Handle multiple file uploads
     */
    public function store(UploadImageRequest $request)
    {
        // Get or create upload session
        $sessionToken = $request->input('session_token', Str::random(64));
        
        $uploadSession = UploadSession::firstOrCreate(
            ['session_token' => $sessionToken],
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $uploadedImages = [];

        foreach ($request->file('images') as $file) {
            // Generate unique filename
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            
            // Store file in organized directory structure (year/month)
            $directory = 'uploads/images/' . date('Y') . '/' . date('m');
            $filePath = $file->storeAs($directory, $filename);

            // Create image record
            $image = Image::create([
                'upload_session_id' => $uploadSession->id,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'pending',
            ]);

            // Update session counter
            $uploadSession->incrementTotalImages();
            $uploadSession->increment('pending_count');

            // Dispatch job to process image
            ProcessImageJob::dispatch($image);

            $uploadedImages[] = [
                'id' => $image->id,
                'original_name' => $image->original_name,
                'size' => $image->human_file_size,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedImages) . ' image(s) uploaded successfully',
            'session_token' => $sessionToken,
            'images' => $uploadedImages,
        ]);
    }
}
