@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="dashboard-grid">
    <!-- Statistics Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon stat-icon-primary">🖼️</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['total_images']) }}</h3>
                <p>Total Images</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-warning">⏳</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['pending_count']) }}</h3>
                <p>Pending Review</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-success">✓</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['approved_count']) }}</h3>
                <p>Approved</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-danger">✗</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['rejected_count']) }}</h3>
                <p>Rejected</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-danger">🚩</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['flagged_count']) }}</h3>
                <p>Auto-Flagged</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-info">📊</div>
            <div class="stat-content">
                <h3>{{ $stats['approval_rate'] }}%</h3>
                <p>Approval Rate</p>
            </div>
        </div>
    </div>

    <!-- Recent Flagged Images -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Recent Flagged Images</h2>
            <a href="{{ route('admin.images.index', ['flagged' => 1]) }}" class="btn btn-sm btn-secondary">View All</a>
        </div>

        @if($recentFlagged->count() > 0)
            <div class="image-grid">
                @foreach($recentFlagged as $image)
                    <div class="image-card">
                        <div class="image-thumbnail">
                            <img src="{{ $image->url }}" alt="{{ $image->original_name }}" loading="lazy">
                            <div class="image-overlay">
                                <a href="{{ route('admin.images.show', $image) }}" class="btn btn-sm btn-primary">
                                    Review
                                </a>
                            </div>
                        </div>
                        <div class="image-info">
                            <p class="image-name">{{ Str::limit($image->original_name, 20) }}</p>
                            <div class="flagged-reasons">
                                @foreach(explode(', ', $image->flagged_reason) as $reason)
                                    <span class="badge badge-danger">{{ $reason }}</span>
                                @endforeach
                            </div>
                            <small class="text-muted">{{ $image->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted">No flagged images found.</p>
        @endif
    </div>

    <!-- Recent Activity -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Recent Activity</h2>
        </div>

        @if($recentActivity->count() > 0)
            <div class="activity-list">
                @foreach($recentActivity as $log)
                    <div class="activity-item">
                        <div class="activity-icon activity-icon-{{ $log->action }}">
                            @if($log->action == 'approve') ✓
                            @elseif($log->action == 'reject') ✗
                            @elseif($log->action == 'auto_flagged') 🚩
                            @else 📝
                            @endif
                        </div>
                        <div class="activity-content">
                            <p>
                                @if($log->user)
                                    <strong>{{ $log->user->name }}</strong>
                                @else
                                    <strong>System</strong>
                                @endif
                                {{ $log->description }}
                                @if($log->image)
                                    - <a href="{{ route('admin.images.show', $log->image_id) }}">{{ $log->image->original_name }}</a>
                                @endif
                            </p>
                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted">No recent activity.</p>
        @endif
    </div>
</div>
@endsection
