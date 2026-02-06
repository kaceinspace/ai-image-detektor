// Admin dashboard functionality
document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // Bulk Actions Handler
    // ============================================
    initializeBulkActions();

    // ============================================
    // Image Lightbox/Modal
    // ============================================
    initializeImageLightbox();

    // ============================================
    // Confirmation Modals
    // ============================================
    initializeConfirmationModals();

    // ============================================
    // Filter Functionality
    // ============================================
    initializeFilters();
});

/**
 * Initialize bulk actions for image list
 */
function initializeBulkActions() {
    const selectAll = document.getElementById('select-all');
    const bulkActionForm = document.getElementById('bulk-action-form');

    if (!bulkActionForm) return;

    // Select all checkbox
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.image-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButton();
        });
    }

    // Individual checkboxes
    document.querySelectorAll('.image-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionButton);
    });

    // Bulk action form submit
    bulkActionForm.addEventListener('submit', function (e) {
        const selectedImages = getSelectedImages();
        const action = document.getElementById('bulk-action-select')?.value;

        if (selectedImages.length === 0) {
            e.preventDefault();
            alert('Please select at least one image');
            return false;
        }

        if (!action) {
            e.preventDefault();
            alert('Please select an action');
            return false;
        }

        const actionText = action === 'approve' ? 'approve' : 'reject';
        const confirmed = confirm(`Are you sure you want to ${actionText} ${selectedImages.length} image(s)?`);

        if (!confirmed) {
            e.preventDefault();
            return false;
        }
    });
}

/**
 * Update bulk action button state
 */
function updateBulkActionButton() {
    const selectedImages = getSelectedImages();
    const bulkActionButton = document.getElementById('bulk-action-button');
    const bulkActionSelect = document.getElementById('bulk-action-select');

    if (bulkActionButton && bulkActionSelect) {
        if (selectedImages.length > 0) {
            bulkActionButton.disabled = false;
            bulkActionButton.textContent = `Apply to ${selectedImages.length} image(s)`;
        } else {
            bulkActionButton.disabled = true;
            bulkActionButton.textContent = 'Apply';
        }
    }
}

/**
 * Get selected image IDs
 */
function getSelectedImages() {
    const checkboxes = document.querySelectorAll('.image-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

/**
 * Initialize image lightbox/modal
 */
function initializeImageLightbox() {
    const images = document.querySelectorAll('.lightbox-trigger');

    images.forEach(img => {
        img.addEventListener('click', function (e) {
            e.preventDefault();
            const imageUrl = this.dataset.imageUrl || this.src;
            showLightbox(imageUrl);
        });
    });
}

/**
 * Show image lightbox
 */
function showLightbox(imageUrl) {
    // Create lightbox if doesn't exist
    let lightbox = document.getElementById('image-lightbox');

    if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'image-lightbox';
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-overlay"></div>
            <div class="lightbox-content">
                <button class="lightbox-close" aria-label="Close">&times;</button>
                <img src="" alt="Lightbox image">
            </div>
        `;
        document.body.appendChild(lightbox);

        // Close on overlay click
        lightbox.querySelector('.lightbox-overlay').addEventListener('click', hideLightbox);
        lightbox.querySelector('.lightbox-close').addEventListener('click', hideLightbox);

        // Close on ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                hideLightbox();
            }
        });
    }

    // Set image and show
    lightbox.querySelector('img').src = imageUrl;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Hide image lightbox
 */
function hideLightbox() {
    const lightbox = document.getElementById('image-lightbox');
    if (lightbox) {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Initialize confirmation modals for approve/reject actions
 */
function initializeConfirmationModals() {
    // Approve buttons
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const imageName = this.dataset.imageName || 'this image';
            const confirmed = confirm(`Are you sure you want to approve "${imageName}"?`);
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Reject buttons
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const form = this.closest('form');
            const imageName = this.dataset.imageName || 'this image';
            const noteField = form?.querySelector('[name="note"]');

            if (noteField && !noteField.value.trim()) {
                const addNote = confirm(`Are you sure you want to reject "${imageName}" without a note?`);
                if (!addNote) {
                    e.preventDefault();
                    noteField.focus();
                    return false;
                }
            }

            const confirmed = confirm(`Are you sure you want to reject "${imageName}"?`);
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Initialize filter functionality
 */
function initializeFilters() {
    const statusFilter = document.getElementById('status-filter');
    const flaggedFilter = document.getElementById('flagged-filter');
    const dateFromFilter = document.getElementById('date-from-filter');
    const dateToFilter = document.getElementById('date-to-filter');

    // Auto-submit form on filter change
    [statusFilter, flaggedFilter, dateFromFilter, dateToFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', function () {
                this.closest('form')?.submit();
            });
        }
    });
}

/**
 * Show notification/toast message
 */
function showNotification(type, message, duration = 5000) {
    const container = document.getElementById('notification-container') || createNotificationContainer();

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
            <span class="notification-message">${message}</span>
        </div>
    `;

    container.appendChild(notification);

    setTimeout(() => notification.classList.add('show'), 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

/**
 * Create notification container if it doesn't exist
 */
function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notification-container';
    container.className = 'notification-container';
    document.body.appendChild(container);
    return container;
}

// Export for use in other scripts if needed
window.adminUtils = {
    showNotification,
    showLightbox,
    hideLightbox
};
