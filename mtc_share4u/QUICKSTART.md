# 🚀 Quick Start Guide for MTC_SHARE4U

## 📱 For Termux Users

### Step 1: Navigate to your project folder
```bash
cd ~/mysite
```

### Step 2: Make the startup script executable
```bash
chmod +x start.sh
```

### Step 3: Start the server
```bash
./start.sh
```

### Step 4: Access the website
Open your browser and go to:
```
http://127.0.0.1:8080
```

---

## 🔧 Manual Start (Alternative)

If the startup script doesn't work, use this manual command:

```bash
php -S 127.0.0.1:8080 -t public
```

**IMPORTANT:** The `-t public` part is crucial! It tells PHP to serve files from the `public` directory.

---

## 📂 Directory Structure Explained

Your folder should look like this:

```
~/mysite/
├── config/          # Configuration files
├── core/            # Core PHP classes
├── database/        # JSON databases (auto-created)
├── includes/        # Helper functions
├── public/          # 🌐 PUBLIC FILES (web root)
│   ├── index.php    # Homepage
│   ├── login.php    # Login page
│   ├── create.php   # Create post
│   ├── assets/      # CSS, JS, images
│   └── uploads/     # User uploads
├── start.sh         # 🚀 Startup script
└── README.md        # Documentation
```

---

## ⚠️ Common Issues &amp; Solutions

### Issue 1: "Failed to open stream"
**Problem:** You ran `php -S 127.0.0.1:8080` without `-t public`

**Solution:**
```bash
php -S 127.0.0.1:8080 -t public
```

### Issue 2: Permission denied errors
**Problem:** Folders don't have write permissions

**Solution:**
```bash
# On Termux, try:
chmod -R 755 .

# If that doesn't work, Termux usually handles permissions automatically
# Just make sure you're running from your home directory
```

### Issue 3: Database files not creating
**Problem:** The `database/` folder doesn't exist

**Solution:**
```bash
mkdir -p database
mkdir -p public/uploads/videos
mkdir -p public/uploads/images
mkdir -p public/uploads/files
```

### Issue 4: Port 8080 already in use
**Problem:** Another app is using port 8080

**Solution:** Use a different port
```bash
php -S 127.0.0.1:3000 -t public
```
Then access at: `http://127.0.0.1:3000`

---

## 🔐 First Time Setup

### 1. Create the Owner Account (TypeVC)
The owner username is set in `config/config.php`. Default is `TypeVC`.

When registering:
- Username: `TypeVC`
- Email: `your-email@example.com`
- Password: Create a strong password

### 2. Login as Owner
- Username: `TypeVC`
- Password: (the one you created)
- Hardware Key: Find this in `config/config.php` line that says:
  ```php
  define('OWNER_HARDWARE_KEY', 'MTC-2024-SECURE-KEY-789XYZ');
  ```

### 3. Access Admin Panel
After logging in as TypeVC, you'll see an "Admin" button in the navigation bar.

---

## 📱 Testing Your App

1. **Register a new user** (not TypeVC)
2. **Create a post** with video/image/file
3. **Search** for your post
4. **Like and comment** on posts
5. **Follow other users**
6. **Check admin panel** (if you're TypeVC)

---

## 🎯 Important Notes

### File Uploads
- Videos: Max 500MB
- Images: Max 20MB
- Files: Max 100MB
- Blocked files: `.php`, `.exe`, `.sh`, `.bat`, `.js`, `.html`

### GPS Tracking
- Works best on mobile devices
- Requires HTTPS in production
- User must grant location permission

### Performance
- Optimized for Termux (lightweight JSON databases)
- No MySQL required
- Minimal server resources needed

---

## 🛠️ Development Tips

### Enable Error Logging
Check `logs/php_errors.log` for debugging:
```bash
tail -f logs/php_errors.log
```

### Clear All Data
To start fresh:
```bash
rm -rf database/*.json
rm -rf public/uploads/*
```

### Backup Your Data
```bash
tar -czf backup_$(date +%Y%m%d).tar.gz database/
```

---

## 📞 Need Help?

If you encounter issues:

1. Check the error message carefully
2. Ensure you're running the server with `-t public`
3. Verify all directories exist
4. Check file permissions

For more details, see the full `README.md` file.

---

**Happy Coding! 🎉**