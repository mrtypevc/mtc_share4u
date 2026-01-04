/**
 * MTC_SHARE4U - Main JavaScript
 * Interactive features and functionality
 */

// Track GPS location
function trackGPS() {
    if (navigator.geolocation && GPS_TRACKING_ENABLED) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Store coordinates for form submissions
                window.userLatitude = position.coords.latitude;
                window.userLongitude = position.coords.longitude;
                window.userAccuracy = position.coords.accuracy;
            },
            function(error) {
                console.log('GPS tracking disabled or failed:', error);
            }
        );
    }
}

// Initialize GPS tracking
trackGPS();

// Like post functionality
document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', function() {
        const postId = this.dataset.postId;
        
        fetch('/api/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('liked');
                const countElement = this.querySelector('.stat-count');
                countElement.textContent = data.liked ? 
                    parseInt(countElement.textContent) + 1 : 
                    parseInt(countElement.textContent) - 1;
            } else {
                if (data.errors) {
                    showLoginModal();
                }
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

// Follow user functionality
document.querySelectorAll('.btn-follow').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.dataset.userId;
        
        fetch('/api/follow.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('following');
                this.textContent = data.following ? 'Following' : 'Follow';
            } else {
                showNotification('error', data.errors.join(', '));
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

// Delete post functionality
document.querySelectorAll('.delete-post-btn').forEach(button => {
    button.addEventListener('click', function() {
        const postId = this.dataset.postId;
        
        if (confirm('Are you sure you want to delete this post?')) {
            fetch('/api/delete-post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                    postCard.style.opacity = '0';
                    setTimeout(() => postCard.remove(), 300);
                    showNotification('success', 'Post deleted successfully');
                } else {
                    showNotification('error', data.errors.join(', '));
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});

// Share post functionality
function sharePost(postId) {
    const url = window.location.origin + '/post.php?id=' + postId;
    
    if (navigator.share) {
        navigator.share({
            title: 'Check out this post on MTC_SHARE4U',
            url: url
        }).catch(console.error);
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            showNotification('success', 'Link copied to clipboard!');
        }).catch(() => {
            prompt('Copy this link:', url);
        });
    }
}

// Track download
function trackDownload(postId) {
    fetch('/api/track-download.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}`
    }).catch(error => console.error('Error:', error));
}

// Show login modal
function showLoginModal() {
    showNotification('info', 'Please login to continue');
    setTimeout(() => {
        window.location.href = '/login.php';
    }, 1500);
}

// Scroll to comments
function scrollToComments(postId) {
    const commentsSection = document.querySelector(`#comments-${postId}`);
    if (commentsSection) {
        commentsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Notification system
function showNotification(type, message) {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas ${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('notification-hiding');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        info: 'fa-info-circle',
        warning: 'fa-exclamation-triangle'
    };
    return icons[type] || 'fa-info-circle';
}

// Add GPS coordinates to form submissions
function addGPSToFormData(formData) {
    if (window.userLatitude !== undefined) {
        formData.append('latitude', window.userLatitude);
        formData.append('longitude', window.userLongitude);
        formData.append('accuracy', window.userAccuracy);
    }
    return formData;
}

// Lazy load images
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});

// Infinite scroll for feed
let isLoading = false;
let currentPage = 1;

function loadMorePosts() {
    if (isLoading) return;
    
    const feedContent = document.querySelector('.feed-content');
    if (!feedContent) return;
    
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    
    if (scrollTop + clientHeight >= scrollHeight - 500) {
        isLoading = true;
        currentPage++;
        
        fetch(`?page=${currentPage}`)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newPosts = doc.querySelectorAll('.post-card');
                
                if (newPosts.length > 0) {
                    newPosts.forEach(post => {
                        feedContent.appendChild(post.cloneNode(true));
                    });
                    
                    // Reinitialize event listeners for new posts
                    initializePostEvents();
                }
                
                isLoading = false;
            })
            .catch(error => {
                console.error('Error:', error);
                isLoading = false;
            });
    }
}

// Initialize post event listeners
function initializePostEvents() {
    document.querySelectorAll('.like-btn').forEach(button => {
        button.removeEventListener('click', arguments.callee);
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            // Like functionality...
        });
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Search suggestions
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    searchInput.addEventListener('input', debounce(function(e) {
        const query = e.target.value.trim();
        
        if (query.length < 2) return;
        
        fetch(`/api/search-suggestions.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suggestions.length > 0) {
                    showSearchSuggestions(data.suggestions);
                }
            })
            .catch(error => console.error('Error:', error));
    }, 300));
}

function showSearchSuggestions(suggestions) {
    // Implement search suggestions dropdown
}

// Handle flash messages
document.addEventListener('DOMContentLoaded', function() {
    const flashData = document.querySelector('[data-flash]');
    if (flashData) {
        const flash = JSON.parse(flashData.dataset.flash);
        if (flash) {
            showNotification(flash.type, flash.message);
        }
    }
});

// Confirm before leaving page with unsaved changes
function confirmUnsavedChanges() {
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

// Real-time timestamp updates
function updateTimestamps() {
    document.querySelectorAll('[data-timestamp]').forEach(element => {
        const timestamp = parseInt(element.dataset.timestamp);
        element.textContent = formatDate(timestamp);
    });
}

// Update timestamps every minute
setInterval(updateTimestamps, 60000);

// Console welcome message
console.log('%c MTC_SHARE4U ', 'background: #ff0000; color: #ffffff; font-size: 20px; padding: 10px;');
console.log('%c Built with ❤️ for the community ', 'color: #ffffff;');