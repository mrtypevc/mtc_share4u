# 🎯 MTC_SHARE4U - HOW TO START

## ✅ FIXED! The structure has been corrected.

## 🚀 Start Your Server Now

```bash
cd ~/mysite
php -S 127.0.0.1:8080
```

**Then open your browser and go to:**
```
http://127.0.0.1:8080
```

---

## 📁 Current Structure (Fixed)

```
~/mysite/
├── index.php          ← Homepage (MOVED TO ROOT)
├── login.php          ← Login page (MOVED TO ROOT)
├── create.php         ← Create post (MOVED TO ROOT)
├── search.php         ← Search page (MOVED TO ROOT)
├── profile.php        ← User profile (MOVED TO ROOT)
├── includes/          ← PHP functions &amp; templates
├── core/              ← Core classes
├── config/            ← Configuration
├── database/          ← JSON databases
├── api/               ← AJAX endpoints
├── assets/            ← CSS, JS, images
├── uploads/           ← User uploads
└── start.sh           ← Startup script
```

---

## 🔑 First Steps

### 1. Access the Website
Go to: `http://127.0.0.1:8080`

### 2. Create an Account
Click "Sign Up" and register

### 3. For Owner Access (TypeVC)
- Username: `TypeVC`
- Check `config/config.php` for the hardware key

---

## ✅ What's Fixed

- ✅ All PHP files moved to root directory
- ✅ Include paths corrected
- ✅ Asset paths updated
- ✅ API paths fixed
- ✅ Directory structure optimized

**Now just run: `php -S 127.0.0.1:8080` and it will work! 🎉**