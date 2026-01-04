# MTC_SHARE4U - Advanced Social Intelligence &amp; File Engine

A high-end Social Media and File Hosting platform built with PHP and JSON (NoSQL), optimized for Termux environment. Features advanced security, intelligent search, and comprehensive user management.

## 🚀 Features

### Core Architecture
- **JSON-based Database**: Fast flat-file storage system optimized for mobile servers
- **Modular PHP Structure**: Clean, maintainable code architecture
- **Termux Optimized**: Lightweight and efficient for Android development environments

### Security System
- **3-Layer Owner Security**: TypeVC access with password + hardware key verification
- **Advanced IP Tracking**: Real-time IP address logging for every action
- **GPS Location Tracking**: Capture exact coordinates (latitude/longitude)
- **IP Ban System**: Owner can block specific IPs from the network
- **Virus Guard**: Blocks dangerous file types (.php, .exe, .sh, .bat)
- **Rate Limiting**: Prevents abuse with smart rate limiting
- **Input Sanitization**: Comprehensive security against XSS and injection attacks

### User Experience (YouTube-Inspired)
- **Intelligent Search Engine**: YouTube-style search with relevance ranking
- **FAQ/Trigger Questions**: Posts can have questions that trigger top search results
- **Modern Dark Mode**: Beautiful, professional dark theme
- **Responsive Design**: Works perfectly on all devices
- **Profile Management**: Editable display name and bio (username permanent)

### Engagement System
- **Real-time Likes**: Instant like/unlike functionality
- **Follow System**: Follow/unfollow users with real-time tracking
- **Comments System**: Comment on posts with external link support
- **Activity Feed**: See posts from users you follow
- **Notifications**: Flash messages for user feedback

### Media Support
- **Video Streaming**: High-speed MP4/WebM streaming
- **Image Gallery**: JPG, PNG, GIF, WebP support
- **File Downloads**: Secure file download with tracking
- **Automatic Thumbnails**: Video thumbnail generation

### Admin Panel (TypeVC - God Mode)
- **Live Traffic Monitoring**: Real-time IP and GPS tracking
- **Post Management**: Delete any post in the system
- **User Restrictions**: Restrict users from downloading files
- **Account Management**: Activate/deactivate user accounts
- **Password Recovery**: Generate recovery codes for users
- **IP Management**: Ban/unban IP addresses
- **System Health**: Monitor disk usage, database size, and active sessions

## 📋 Requirements

- PHP 8.x or higher
- Web server (Apache/Nginx) or PHP built-in server
- Termux (for Android development) or Linux environment
- FFmpeg (optional, for video thumbnail generation)

## 🛠️ Installation

### 1. Clone or Download
```bash
git clone <repository-url>
cd mtc_share4u
```

### 2. Set Permissions
```bash
chmod -R 755 .
chmod -R 777 database public/uploads
```

### 3. Configure Owner
Edit `config/config.php`:
```php
define('OWNER_USERNAME', 'TypeVC');
define('OWNER_HARDWARE_KEY', 'YOUR_SECURE_KEY_HERE');
```

### 4. Start Server
Using PHP built-in server:
```bash
php -S localhost:8080 -t public
```

Or using Apache/Nginx:
- Point document root to `public/` directory
- Ensure `AllowOverride All` is set for `.htaccess` support

### 5. Access Application
Open browser and navigate to `http://localhost:8080`

## 📁 Project Structure

```
mtc_share4u/
├── config/
│   └── config.php          # Main configuration
├── core/
│   ├── AdminPanel.php      # Admin panel logic
│   ├── Auth.php            # Authentication system
│   ├── Database.php        # JSON database abstraction
│   ├── InteractionManager.php # Likes, follows, comments
│   ├── PostManager.php     # Post management
│   └── Security.php        # Security functions
├── database/               # JSON database files (auto-created)
├── includes/
│   └── functions.php       # Helper functions
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css   # Main stylesheet
│   │   ├── js/
│   │   │   └── main.js     # JavaScript functionality
│   │   └── images/         # Static images
│   ├── uploads/            # User uploads
│   │   ├── videos/
│   │   ├── images/
│   │   ├── files/
│   │   └── thumbnails/
│   ├── admin/              # Admin panel pages
│   ├── includes/           # Template includes
│   ├── index.php           # Main feed
│   ├── login.php           # Login page
│   ├── register.php        # Registration page
│   ├── create.php          # Create post
│   ├── search.php          # Search page
│   └── profile.php         # User profile
└── README.md               # This file
```

## 🔐 Security Features

### Owner Access (TypeVC)
- Username: `TypeVC` (configurable)
- Requires: Password + Hardware Key
- Can delete any post
- Can ban any IP
- Can restrict any user
- Full system access

### File Security
- Blocked extensions: `.php`, `.exe`, `.sh`, `.bat`, `.cmd`, `.ps1`, `.js`, `.html`, `.htm`
- MIME type verification
- File size limits
- Upload directory protection

### Rate Limiting
- Posts: 10 per hour
- Comments: 50 per hour
- Search: 100 per hour
- Login attempts: 5 per 15 minutes

## 🔧 Configuration

### Database Paths
All databases are stored as JSON files in the `database/` directory:
- `users.json` - User accounts
- `posts.json` - Posts and media
- `comments.json` - Comments
- `likes.json` - Likes
- `follows.json` - Follow relationships
- `tracking.json` - IP/GPS tracking
- `banned_ips.json` - Banned IP addresses
- `recovery_codes.json` - Password recovery codes

### File Upload Limits
- Videos: 500MB
- Images: 20MB
- Files: 100MB

## 📱 API Endpoints

The application includes AJAX endpoints for:
- `/api/like.php` - Like/unlike posts
- `/api/follow.php` - Follow/unfollow users
- `/api/delete-post.php` - Delete posts
- `/api/track-download.php` - Track file downloads
- `/api/search-suggestions.php` - Search suggestions

## 🎨 Customization

### Changing Theme
Edit `public/assets/css/style.css` and modify CSS variables:
```css
:root {
    --bg-primary: #0f0f0f;
    --accent-primary: #ff0000;
    --accent-secondary: #3ea6ff;
    /* ... more variables */
}
```

### Modifying File Types
Edit `config/config.php`:
```php
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('BLOCKED_FILE_TYPES', ['php', 'exe', 'sh', 'bat']);
```

## 🐛 Troubleshooting

### Database Not Creating
Ensure `database/` directory is writable:
```bash
chmod 777 database
```

### Uploads Failing
Check `public/uploads/` permissions:
```bash
chmod -R 777 public/uploads
```

### GPS Not Working
- Browser requires HTTPS for geolocation
- User must grant location permission
- Works best on mobile devices

## 📄 License

This project is provided as-is for educational and personal use.

## 👥 Credits

Built with ❤️ using PHP, JSON, and modern web technologies.

## 🆘 Support

For issues or questions, contact TypeVC at typepanel@gmail.com

---

**Note**: This is a high-performance system designed for Termux environments. Ensure your server meets the minimum requirements for optimal performance.