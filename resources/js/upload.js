// Upload page - Dropzone.js initialization
document.addEventListener('DOMContentLoaded', function () {
    // Check if we're on the upload page
    const dropzoneElement = document.getElementById('image-dropzone');
    if (!dropzoneElement) return;

    // Check if Dropzone is available
    if (typeof Dropzone === 'undefined') {
        console.error('Dropzone.js is not loaded. Please run: npm install && npm run build');
        return;
    }

    // Disable auto-discover
    Dropzone.autoDiscover = false;

    // Initialize Dropzone
    const myDropzone = new Dropzone("#image-dropzone", {
        url: "/upload",
        method: "post",
        paramName: "images",
        maxFilesize: 10, // MB
        acceptedFiles: "image/jpeg,image/png,image/jpg,image/gif,image/webp",
        uploadMultiple: true,
        parallelUploads: 5,
        maxFiles: 20,
        addRemoveLinks: true,
        dictDefaultMessage: "Drag & drop images here or click to browse<br><small>Supported: JPG, PNG, GIF, WebP (Max 10MB each)</small>",
        dictFileTooBig: "File is too large ({{filesize}}MB). Max file size: {{maxFilesize}}MB",
        dictInvalidFileType: "Invalid file type. Only images are allowed",
        dictRemoveFile: "Remove",
        dictCancelUpload: "Cancel",

        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },

        init: function () {
            this.on("successmultiple", function (files, response) {
                console.log('Upload successful:', response);

                // Show success notification
                showNotification('success', `${files.length} image(s) uploaded successfully!`);

                // Remove files after successful upload
                setTimeout(() => {
                    files.forEach(file => this.removeFile(file));
                }, 2000);
            });

            this.on("errormultiple", function (files, errorMessage, xhr) {
                console.error('Upload error:', errorMessage);

                // Show error notification
                let message = 'Upload failed. Please try again.';
                if (typeof errorMessage === 'object' && errorMessage.message) {
                    message = errorMessage.message;
                } else if (typeof errorMessage === 'string') {
                    message = errorMessage;
                }

                showNotification('error', message);
            });

            this.on("error", function (file, errorMessage) {
                console.error('File error:', errorMessage);

                // Handle validation errors
                if (typeof errorMessage === 'object' && errorMessage.errors) {
                    const errors = Object.values(errorMessage.errors).flat();
                    showNotification('error', errors.join('<br>'));
                }
            });

            this.on("addedfile", function (file) {
                console.log('File added:', file.name);
            });

            this.on("uploadprogress", function (file, progress, bytesSent) {
                console.log('Upload progress:', file.name, progress + '%');
            });
        }
    });

    // Helper function to show notifications
    function showNotification(type, message) {
        const container = document.getElementById('notification-container') || createNotificationContainer();

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${type === 'success' ? '✓' : '✗'}</span>
                <span class="notification-message">${message}</span>
            </div>
        `;

        container.appendChild(notification);

        // Trigger animation
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    function createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        document.body.appendChild(container);
        return container;
    }
});
