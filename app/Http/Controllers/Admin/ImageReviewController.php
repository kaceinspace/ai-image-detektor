<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ImageReviewController extends Controller
{
    /**
     * List images with filters and pagination
     */
    public function index(Request $request)
    {
        $query = Image::with(['moderationResult', 'uploadSession', 'reviewer']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter flagged only
        if ($request->boolean('flagged')) {
            $query->where('is_flagged', true);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $images = $query->paginate(20);

        return view('admin.images.index', compact('images'));
    }

    /**
     * Show image detail
     */
    public function show(Image $image)
    {
        $image->load(['moderationResult', 'uploadSession', 'reviewer', 'activityLogs.user']);
        
        return view('admin.images.show', compact('image'));
    }

    /**
     * Approve image
     */
    public function approve(Request $request, Image $image)
    {
        $image->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('note'),
        ]);

        // Update session counters
        $image->uploadSession->updateCounters();

        // Create activity log
        ActivityLog::createLog(
            action: 'approve',
            userId: auth()->id(),
            imageId: $image->id,
            description: 'Image approved by ' . auth()->user()->name,
            metadata: ['note' => $request->input('note')]
        );

        return redirect()
            ->route('admin.images.index')
            ->with('success', 'Image approved successfully');
    }

    /**
     * Reject image
     */
    public function reject(Request $request, Image $image)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $image->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->note,
        ]);

        // Update session counters
        $image->uploadSession->updateCounters();

        // Create activity log
        ActivityLog::createLog(
            action: 'reject',
            userId: auth()->id(),
            imageId: $image->id,
            description: 'Image rejected by ' . auth()->user()->name,
            metadata: ['note' => $request->note]
        );

        return redirect()
            ->route('admin.images.index')
            ->with('success', 'Image rejected successfully');
    }

    /**
     * Bulk action (approve/reject multiple images)
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'image_ids' => 'required|array',
            'image_ids.*' => 'exists:images,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $images = Image::whereIn('id', $request->image_ids)->get();
        $count = 0;

        foreach ($images as $image) {
            $image->update([
                'status' => $request->action === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $request->note,
            ]);

            // Update session counters
            $image->uploadSession->updateCounters();

            // Create activity log
            ActivityLog::createLog(
                action: $request->action,
                userId: auth()->id(),
                imageId: $image->id,
                description: 'Bulk ' . $request->action . ' by ' . auth()->user()->name,
                metadata: ['note' => $request->note]
            );

            $count++;
        }

        return redirect()
            ->route('admin.images.index')
            ->with('success', "{$count} image(s) {$request->action}d successfully");
    }
}
