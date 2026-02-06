<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Images - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/dropzone@6.0.0-beta.2/dist/dropzone.css" type="text/css" />
</head>
<body class="upload-page">
    <div class="upload-container">
        <div class="upload-header">
            <h1>Upload Images for Moderation</h1>
            <p>Upload multiple images. They will be automatically analyzed for inappropriate content.</p>
        </div>

        <div class="upload-card">
            <form action="{{ route('upload.store') }}" 
                  class="dropzone" 
                  id="imageDropzone"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                <div class="dz-message">
                    <div class="icon">📤</div>
                    <h3>Drop files here or click to upload</h3>
                    <p>Supported formats: JPEG, PNG, GIF, WEBP (Max 10MB per file)</p>
                </div>
            </form>
        </div>

        <div class="upload-info">
            <div class="info-card">
                <span class="info-icon">✅</span>
                <div>
                    <h4>Automatic Analysis</h4>
                    <p>Images are analyzed using AI to detect inappropriate content</p>
                </div>
            </div>
            <div class="info-card">
                <span class="info-icon">🔍</span>
                <div>
                    <h4>Admin Review</h4>
                    <p>All flagged images are manually reviewed by moderators</p>
                </div>
            </div>
            <div class="info-card">
                <span class="info-icon">🔒</span>
                <div>
                    <h4>Secure Storage</h4>
                    <p>Your images are stored securely and reviewed confidentially</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/dropzone@6.0.0-beta.2/dist/dropzone-min.js"></script>
    <script>
        Dropzone.autoDiscover = false;
        
        let sessionToken = localStorage.getItem('upload_session_token');
        
        const dropzone = new Dropzone("#imageDropzone", {
            paramName: "images",
            maxFilesize: 10, // MB
            acceptedFiles: "image/jpeg,image/png,image/jpg,image/gif,image/webp",
            addRemoveLinks: true,
            dictDefaultMessage: "Drop files here or click to upload",
            uploadMultiple: true,
            parallelUploads: 5,
            maxFiles: 20,
            
            init: function() {
                this.on("sendingmultiple", function(files, xhr, formData) {
                    if (sessionToken) {
                        formData.append('session_token', sessionToken);
                    }
                });
                
                this.on("successmultiple", function(files, response) {
                    if (response.session_token) {
                        sessionToken = response.session_token;
                        localStorage.setItem('upload_session_token', sessionToken);
                    }
                    
                    // Show success message
                    showNotification('success', response.message || 'Upload successful!');
                    
                    // Remove files after successful upload
                    setTimeout(() => {
                        files.forEach(file => this.removeFile(file));
                    }, 2000);
                });
                
                this.on("errormultiple", function(files, errorMessage) {
                    showNotification('error', errorMessage || 'Upload failed. Please try again.');
                });
                
                this.on("error", function(file, errorMessage) {
                    showNotification('error', errorMessage || 'Upload failed. Please try again.');
                });
            }
        });
        
        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.classList.add('notification', `notification-${type}`);
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
