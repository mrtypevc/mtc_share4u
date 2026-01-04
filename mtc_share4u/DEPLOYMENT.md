# 🚀 MTC_SHARE4U - Complete Deployment Guide

## 📦 What's Included

✅ **Complete Social Media Platform**
- User authentication &amp; registration
- Post creation (videos, images, files)
- YouTube-style intelligent search
- Like, follow, comment systems
- Admin panel with full control
- GPS &amp; IP tracking
- Security features (virus guard, rate limiting)

✅ **21 Files Created**
- 18 PHP files
- 1 CSS file
- 1 JavaScript file
- 1 Shell script

✅ **Ready for Termux**
- Optimized for mobile development
- JSON-based databases (no MySQL needed)
- Lightweight and fast

---

## 🎯 Quick Deployment Steps

### Option 1: Using the Startup Script (Recommended)

```bash
# Navigate to your project directory
cd ~/mysite

# Make script executable (if not already)
chmod +x start.sh

# Start the server
./start.sh
```

### Option 2: Manual Start

```bash
# Navigate to your project directory
cd ~/mysite

# Start PHP server pointing to public folder
php -S 127.0.0.1:8080 -t public
```

---

## 🔑 First-Time Setup

### 1. Create Owner Account (TypeVC)

The system is pre-configured with:
- **Owner Username**: `TypeVC` (can be changed in `config/config.php`)
- **Hardware Key**: Check `config/config.php` line 14

**Steps:**
1. Go to `http://127.0.0.1:8080`
2. Click "Sign Up"
3. Register with username: `TypeVC`
4. Create a strong password
5. Use the email configured in config

### 2. Login as Owner

1. Go to `http://127.0.0.1:8080/login.php`
2. Enter username: `TypeVC`
3. Enter your password
4. **Enter Hardware Key** (found in `config/config.php` line 14)
5. Click Login

---

## 🚨 Troubleshooting

### Issue: "Failed to open stream"
**Cause**: Running server without `-t public`
**Fix**: `php -S 127.0.0.1:8080 -t public`

### Issue: Uploads not working
**Cause**: Upload directories don't exist
**Fix**: `mkdir -p public/uploads/{videos,images,files,thumbnails}`

### Issue: Database not creating
**Cause**: Database directory doesn't exist
**Fix**: `mkdir -p database`

---

**Congratulations! Your MTC_SHARE4U platform is ready! 🚀**