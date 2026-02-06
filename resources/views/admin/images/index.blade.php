@extends('layouts.admin')

@section('title', 'Images')
@section('page-title', 'Image Review')

@section('content')
<div class="images-page">
    <!-- Filters -->
    <form method="GET" class="filters-form">
        <div class="filters-row">
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                </select>
            </div>

            <div class="filter-group">
                <label>
                    <input type="checkbox" name="flagged" value="1" {{ request('flagged') ? 'checked' : '' }} onchange="this.form.submit()">
                    Flagged Only
                </label>
            </div>

            <div class="filter-group">
                <label>Sort:</label>
                <select name="sort_by" onchange="this.form.submit()">
                    <option value="created_at" {{ request('sort_by', 'created_at') == 'created_at' ? 'selected' : '' }}>Upload Date</option>
                    <option value="reviewed_at" {{ request('sort_by') == 'reviewed_at' ? 'selected' : '' }}>Review Date</option>
                    <option value="file_size" {{ request('sort_by') == 'file_size' ? 'selected' : '' }}>File Size</option>
                </select>
                <select name="sort_order" onchange="this.form.submit()">
                    <option value="desc" {{ request('sort_order', 'desc') == 'desc' ? 'selected' : '' }}>Newest First</option>
                    <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>

            <div class="filter-group">
                <a href="{{ route('admin.images.index') }}" class="btn btn-sm btn-secondary">Clear Filters</a>
            </div>
        </div>
    </form>

    <!-- Bulk Actions -->
    <div class="bulk-actions" id="bulkActionsBar" style="display:none;">
        <span id="selectedCount">0</span> images selected
        <button type="button" class="btn btn-sm btn-success" onclick="bulkAction('approve')">Approve Selected</button>
        <button type="button" class="btn btn-sm btn-danger" onclick="bulkAction('reject')">Reject Selected</button>
    </div>

    <!-- Images Table -->
    @if($images->count() > 0)
        <div class="images-table-wrapper">
            <table class="images-table">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                        </th>
                        <th width="100">Thumbnail</th>
                        <th>Filename</th>
                        <th>Status</th>
                        <th>Flags</th>
                        <th>Uploaded</th>
                        <th>Reviewed</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($images as $image)
                        <tr>
                            <td>
                                <input type="checkbox" class="image-checkbox" name="image_ids[]" value="{{ $image->id }}" onchange="updateBulkActions()">
                            </td>
                            <td>
                                <div class="table-thumbnail">
                                    <img src="{{ $image->url }}" alt="{{ $image->original_name }}" loading="lazy">
                                </div>
                            </td>
                            <td>
                                <strong>{{ Str::limit($image->original_name, 30) }}</strong><br>
                                <small class="text-muted">{{ $image->human_file_size }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $image->status == 'approved' ? 'success' : ($image->status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($image->status) }}
                                </span>
                            </td>
                            <td>
                                @if($image->is_flagged)
                                    @foreach(explode(', ', $image->flagged_reason) as $reason)
                                        <span class="badge badge-danger">{{ $reason }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $image->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if($image->reviewed_at)
                                    {{ $image->reviewed_at->format('M d, Y H:i') }}<br>
                                    <small class="text-muted">by {{ $image->reviewer->name }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.images.show', $image) }}" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper">
            {{ $images->withQueryString()->links() }}
        </div>
    @else
        <p class="text-muted text-center">No images found.</p>
    @endif
</div>

@push('scripts')
<script>
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.image-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateBulkActions();
    }

    function updateBulkActions() {
        const checkboxes = document.querySelectorAll('.image-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionsBar');
        const count = document.getElementById('selectedCount');
        
        if (checkboxes.length > 0) {
            bulkBar.style.display = 'flex';
            count.textContent = checkboxes.length;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    function bulkAction(action) {
        const checkboxes = document.querySelectorAll('.image-checkbox:checked');
        const imageIds = Array.from(checkboxes).map(cb => cb.value);
        
        if (imageIds.length === 0) {
            alert('Please select at least one image.');
            return;
        }

        let note = null;
        if (action === 'reject') {
            note = prompt('Please provide a reason for rejection:');
            if (!note) return;
        }

        const confirmMsg = `Are you sure you want to ${action} ${imageIds.length} image(s)?`;
        if (!confirm(confirmMsg)) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.images.bulk-action") }}';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="action" value="${action}">
            ${imageIds.map(id => `<input type="hidden" name="image_ids[]" value="${id}">`).join('')}
            ${note ? `<input type="hidden" name="note" value="${note}">` : ''}
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
@endsection
