<?php
/**
 * MTC_SHARE4U - Create Post Page
 */

require_once __DIR__ . '/includes/functions.php';

// Require login
Auth::requireLogin();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $mediaType = $_POST['media_type'] ?? '';
    $triggerQuestions = array_filter(array_map('trim', explode("\n", $_POST['trigger_questions'] ?? '')));
    
    if (empty($mediaType)) {
        $error = 'Please select a media type';
    } elseif (empty($_FILES['media']['name'])) {
        $error = 'Please select a file to upload';
    } else {
        $result = PostManager::createPost($userId, $title, $content, $mediaType, $_FILES['media'], $triggerQuestions);
        
        if ($result['success']) {
            setFlashMessage('success', 'Post created successfully!');
            redirect('post.php?id=' . $result['post_id']);
        } else {
            $error = implode('<br>', $result['errors']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - MTC_SHARE4U</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .create-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .create-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .create-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 1rem;
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: border-color var(--transition-speed);
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-secondary);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .media-type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .media-type-option {
            flex: 1;
            padding: 1rem;
            background-color: var(--bg-tertiary);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all var(--transition-speed);
            text-align: center;
        }
        
        .media-type-option:hover {
            border-color: var(--accent-secondary);
        }
        
        .media-type-option.selected {
            border-color: var(--accent-secondary);
            background-color: rgba(62, 166, 255, 0.1);
        }
        
        .media-type-option i {
            font-size: 2rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .media-type-option.selected i {
            color: var(--accent-secondary);
        }
        
        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-speed);
            margin-bottom: 1.5rem;
        }
        
        .file-upload-area:hover {
            border-color: var(--accent-secondary);
            background-color: var(--bg-tertiary);
        }
        
        .file-upload-area i {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .file-upload-area p {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .file-upload-area small {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background-color: var(--accent-primary);
            color: var(--text-primary);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-speed);
        }
        
        .btn-submit:hover {
            background-color: var(--accent-danger);
            transform: translateY(-2px);
        }
        
        .preview-container {
            margin-top: 1rem;
            display: none;
        }
        
        .preview-container.show {
            display: block;
        }
        
        .preview-media {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1><i class="fas fa-share-nodes"></i> MTC_SHARE4U</h1>
            </div>
            
            <div class="nav-actions">
                <a href="index.php" class="nav-login"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </nav>

    <!-- Create Content -->
    <div class="create-container">
        <div class="create-header">
            <h2><i class="fas fa-plus-circle"></i> Create New Post</h2>
            <p style="color: var(--text-secondary);">Share your content with the community</p>
        </div>
        
        <div class="create-card">
            <?php if ($error): ?>
                <div class="alert alert-error" style="background-color: rgba(231, 76, 60, 0.1); border: 1px solid var(--accent-danger); color: var(--accent-danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" id="createPostForm">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="Enter an engaging title for your post">
                </div>
                
                <div class="form-group">
                    <label for="content">Description</label>
                    <textarea id="content" name="content" 
                              placeholder="Tell people about your post (optional)"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Media Type *</label>
                    <div class="media-type-selector">
                        <div class="media-type-option selected" data-type="video">
                            <i class="fas fa-video"></i>
                            <p>Video</p>
                        </div>
                        <div class="media-type-option" data-type="image">
                            <i class="fas fa-image"></i>
                            <p>Image</p>
                        </div>
                        <div class="media-type-option" data-type="file">
                            <i class="fas fa-file"></i>
                            <p>File</p>
                        </div>
                    </div>
                    <input type="hidden" id="media_type" name="media_type" value="video">
                </div>
                
                <div class="form-group">
                    <label for="media">Upload File *</label>
                    <div class="file-upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click or drag file here to upload</p>
                        <small id="fileTypeInfo">Supports: MP4, WebM, MOV (Max 500MB)</small>
                        <input type="file" id="media" name="media" style="display: none;" 
                               accept="video/*" required>
                    </div>
                    
                    <div class="preview-container" id="previewContainer">
                        <p style="margin-bottom: 0.5rem; color: var(--text-secondary);">Preview:</p>
                        <div id="preview"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="trigger_questions">
                        FAQ / Trigger Questions
                        <small style="color: var(--text-secondary);">(One per line)</small>
                    </label>
                    <textarea id="trigger_questions" name="trigger_questions" 
                              placeholder="What is this video about?&#10;How does it work?&#10;Why should I watch this?"></textarea>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                        These questions help users find your post when searching
                    </small>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Create Post
                </button>
            </form>
        </div>
    </div>

    <script>
        // Media type selection
        document.querySelectorAll('.media-type-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.media-type-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                const mediaType = this.dataset.type;
                document.getElementById('media_type').value = mediaType;
                
                // Update file input accept attribute
                const fileInput = document.getElementById('media');
                const fileInfo = document.getElementById('fileTypeInfo');
                
                switch(mediaType) {
                    case 'video':
                        fileInput.accept = 'video/*';
                        fileInfo.textContent = 'Supports: MP4, WebM, MOV (Max 500MB)';
                        break;
                    case 'image':
                        fileInput.accept = 'image/*';
                        fileInfo.textContent = 'Supports: JPG, PNG, GIF, WebP (Max 20MB)';
                        break;
                    case 'file':
                        fileInput.accept = '.pdf,.doc,.docx,.txt,.zip,.rar,.mp3,.wav';
                        fileInfo.textContent = 'Supports: PDF, DOC, ZIP, MP3, etc. (Max 100MB)';
                        break;
                }
                
                // Clear previous selection
                fileInput.value = '';
                document.getElementById('previewContainer').classList.remove('show');
            });
        });
        
        // File upload area click
        document.getElementById('uploadArea').addEventListener('click', function() {
            document.getElementById('media').click();
        });
        
        // Drag and drop
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--accent-secondary)';
            this.style.backgroundColor = 'var(--bg-tertiary)';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--border-color)';
            this.style.backgroundColor = '';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--border-color)';
            this.style.backgroundColor = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('media').files = files;
                handleFileSelect(files[0]);
            }
        });
        
        // File selection
        document.getElementById('media').addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileSelect(this.files[0]);
            }
        });
        
        function handleFileSelect(file) {
            const previewContainer = document.getElementById('previewContainer');
            const preview = document.getElementById('preview');
            const mediaType = document.getElementById('media_type').value;
            
            preview.innerHTML = '';
            
            if (mediaType === 'image') {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="preview-media" alt="Preview">`;
                    previewContainer.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else if (mediaType === 'video') {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(file);
                video.className = 'preview-media';
                video.controls = true;
                preview.appendChild(video);
                previewContainer.classList.add('show');
            } else {
                preview.innerHTML = `
                    <div style="padding: 1rem; background-color: var(--bg-tertiary); border-radius: 10px;">
                        <i class="fas fa-file fa-2x" style="color: var(--accent-secondary);"></i>
                        <p style="margin-top: 0.5rem;">${file.name}</p>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">${formatFileSize(file.size)}</p>
                    </div>
                `;
                previewContainer.classList.add('show');
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        }
        
        // Add GPS coordinates
        document.getElementById('createPostForm').addEventListener('submit', function(e) {
            if (window.userLatitude !== undefined) {
                const latitudeInput = document.createElement('input');
                latitudeInput.type = 'hidden';
                latitudeInput.name = 'latitude';
                latitudeInput.value = window.userLatitude;
                
                const longitudeInput = document.createElement('input');
                longitudeInput.type = 'hidden';
                longitudeInput.name = 'longitude';
                longitudeInput.value = window.userLongitude;
                
                const accuracyInput = document.createElement('input');
                accuracyInput.type = 'hidden';
                accuracyInput.name = 'accuracy';
                accuracyInput.value = window.userAccuracy;
                
                this.appendChild(latitudeInput);
                this.appendChild(longitudeInput);
                this.appendChild(accuracyInput);
            }
        });
    </script>
</body>
</html>