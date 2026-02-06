@extends('layouts.admin')

@section('title', 'Image Detail')
@section('page-title', 'Image Detail')

@section('content')
<div class="image-detail-page">
    <div class="detail-grid">
        <!-- Image Preview -->
        <div class="image-preview-section">
            <div class="image-preview-card">
                <img src="{{ $image->url }}" alt="{{ $image->original_name }}" class="preview-image">
            </div>

            <div class="image-meta">
                <h3>{{ $image->original_name }}</h3>
                <div class="meta-row">
                    <span class="meta-label">File Size:</span>
                    <span>{{ $image->human_file_size }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">MIME Type:</span>
                    <span>{{ $image->mime_type }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Uploaded:</span>
                    <span>{{ $image->created_at->format('M d, Y H:i:s') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Status:</span>
                    <span class="badge badge-{{ $image->status == 'approved' ? 'success' : ($image->status == 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($image->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Moderation Results -->
        <div class="moderation-section">
            @if($image->moderationResult)
                <div class="section-card">
                    <h3>AI Moderation Results</h3>
                    
                    <div class="likelihood-grid">
                        <div class="likelihood-item">
                            <span class="likelihood-label">Adult Content:</span>
                            <span class="badge badge-{{ App\Models\ModerationResult::getLikelihoodClass($image->moderationResult->adult_likelihood) }}">
                                {{ $image->moderationResult->adult_likelihood }}
                            </span>
                        </div>

                        <div class="likelihood-item">
                            <span class="likelihood-label">Violence:</span>
                            <span class="badge badge-{{ App\Models\ModerationResult::getLikelihoodClass($image->moderationResult->violence_likelihood) }}">
                                {{ $image->moderationResult->violence_likelihood }}
                            </span>
                        </div>

                        <div class="likelihood-item">
                            <span class="likelihood-label">Racy Content:</span>
                            <span class="badge badge-{{ App\Models\ModerationResult::getLikelihoodClass($image->moderationResult->racy_likelihood) }}">
                                {{ $image->moderationResult->racy_likelihood }}
                            </span>
                        </div>

                        <div class="likelihood-item">
                            <span class="likelihood-label">Medical:</span>
                            <span class="badge badge-{{ App\Models\ModerationResult::getLikelihoodClass($image->moderationResult->medical_likelihood) }}">
                                {{ $image->moderationResult->medical_likelihood }}
                            </span>
                        </div>

                        <div class="likelihood-item">
                            <span class="likelihood-label">Spoof/Fake:</span>
                            <span class="badge badge-{{ App\Models\ModerationResult::getLikelihoodClass($image->moderationResult->spoof_likelihood) }}">
                                {{ $image->moderationResult->spoof_likelihood }}
                            </span>
                        </div>
                    </div>

                    @if($image->is_flagged)
                        <div class="alert alert-danger mt-3">
                            <strong>⚠️ Auto-Flagged:</strong> {{ $image->flagged_reason }}
                        </div>
                    @endif

                    <div class="meta-row mt-3">
                        <span class="meta-label">Analysis Time:</span>
                        <span>{{ $image->moderationResult->processing_time_ms }}ms</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Analyzed At:</span>
                        <span>{{ $image->moderationResult->analyzed_at->format('M d, Y H:i:s') }}</span>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    Moderation analysis not yet complete or failed.
                </div>
            @endif

            <!-- Review Form -->
            @if($image->status === 'pending' || $image->status === 'processing')
                <div class="section-card">
                    <h3>Review Action</h3>
                    
                    <form action="{{ route('admin.images.approve', $image) }}" method="POST" class="review-form">
                        @csrf
                        <textarea name="note" class="form-control" placeholder="Optional note..." rows="3"></textarea>
                        <button type="submit" class="btn btn-success btn-block mt-2">✓ Approve Image</button>
                    </form>

                    <form action="{{ route('admin.images.reject', $image) }}" method="POST" class="review-form mt-2">
                        @csrf
                        <textarea name="note" class="form-control" placeholder="Reason for rejection (required)..." rows="3" required></textarea>
                        <button type="submit" class="btn btn-danger btn-block mt-2">✗ Reject Image</button>
                    </form>
                </div>
            @else
                <div class="section-card">
                    <h3>Review Information</h3>
                    <div class="meta-row">
                        <span class="meta-label">Reviewed By:</span>
                        <span>{{ $image->reviewer->name ?? 'N/A' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Reviewed At:</span>
                        <span>{{ $image->reviewed_at ? $image->reviewed_at->format('M d, Y H:i:s') : 'N/A' }}</span>
                    </div>
                    @if($image->review_note)
                        <div class="meta-row">
                            <span class="meta-label">Note:</span>
                            <span>{{ $image->review_note }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Activity History -->
            @if($image->activityLogs->count() > 0)
                <div class="section-card">
                    <h3>Activity History</h3>
                    <div class="activity-list">
                        @foreach($image->activityLogs as $log)
                            <div class="activity-item-detail">
                                <div class="activity-icon activity-icon-{{ $log->action }}">
                                    @if($log->action == 'approve') ✓
                                    @elseif($log->action == 'reject') ✗
                                    @elseif($log->action == 'auto_flagged') 🚩
                                    @else 📝
                                    @endif
                                </div>
                                <div>
                                    <p><strong>{{ $log->action }}</strong> by {{ $log->user->name ?? 'System' }}</p>
                                    @if($log->description)
                                        <p class="text-muted">{{ $log->description }}</p>
                                    @endif
                                    <small class="text-muted">{{ $log->created_at->format('M d, Y H:i:s') }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="actions-bar">
        <a href="{{ route('admin.images.index') }}" class="btn btn-secondary">← Back to List</a>
    </div>
</div>
@endsection
