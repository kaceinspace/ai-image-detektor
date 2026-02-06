<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\UploadSession;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard with statistics
     */
    public function index()
    {
        $stats = [
            'total_images' => Image::count(),
            'pending_count' => Image::where('status', 'pending')->count(),
            'approved_count' => Image::where('status', 'approved')->count(),
            'rejected_count' => Image::where('status', 'rejected')->count(),
            'flagged_count' => Image::where('is_flagged', true)->count(),
            'total_sessions' => UploadSession::count(),
        ];

        // Calculate approval rate
        $reviewedTotal = $stats['approved_count'] + $stats['rejected_count'];
        $stats['approval_rate'] = $reviewedTotal > 0 
            ? round(($stats['approved_count'] / $reviewedTotal) * 100, 1)
            : 0;

        // Recent flagged images (last 10)
        $recentFlagged = Image::with(['moderationResult', 'uploadSession'])
            ->where('is_flagged', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Recent activity (last 20)
        $recentActivity = \App\Models\ActivityLog::with(['user', 'image'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentFlagged', 'recentActivity'));
    }
}
