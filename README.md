# 🚀 User Profile System - Full-Stack CRUD Application

A production-ready **CodeIgniter 4** web application featuring secure file uploads, paginated user listings, and email notifications.

## 📋 Quick Start

**Live Features:**
- ✅ Secure photo upload (PNG/JPG, max 2MB)
- ✅ Server-side MIME & extension validation
- ✅ Paginated user listing (3 per page)
- ✅ Search filtering by name/email
- ✅ Email notifications on registration
- ✅ Caching layer for performance
- ✅ Bootstrap 5 responsive UI

## 📚 Documentation

**👉 [COMPLETE TECHNICAL DOCUMENTATION →](./DOCUMENTATION.md)**

The main documentation includes:
- 📂 Full file system architecture
- 🔍 Line-by-line code function breakdown
- 🛡️ Security features & risk mitigation
- 💾 Database schema & caching strategy
- 🧪 Testing checklist
- 📸 Screenshots needed for visual reference

## 🔧 Quick Setup (5 minutes)

### Prerequisites
- PHP 8.2+
- MySQL 5.7+
- Composer
- Web server (Apache/Nginx)

### Installation

**1. Clone & Install**
```bash
cd Week_12_13_fupl_system
composer install
```

**2. Configure Environment**
```bash
cp env .env
```

Edit `.env`:
```env
database.default.hostname = localhost
database.default.username = root
database.default.password = your_password
database.default.database = fupl_system
app.baseURL = http://localhost:8080/
```

**3. Create Database**
```sql
CREATE DATABASE fupl_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE fupl_system;

CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**4. Create Uploads Directory**
```bash
mkdir -p public/uploads
chmod 755 public/uploads
```

**5. Start Development Server**
```bash
php spark serve
```

Visit: **http://localhost:8080**

## 🎯 Core Components

| Component | Location | Purpose |
|-----------|----------|---------|
| **Controller** | `app/Controllers/UserController.php` | Business logic & request handling |
| **Model** | `app/Models/UserModel.php` | Database abstraction layer |
| **Upload Form** | `app/Views/users/create.php` | File upload interface |
| **User Listing** | `app/Views/users/index.php` | Paginated table with search |
| **Routes** | `app/Config/Routes.php` | URL mapping configuration |
| **Layout** | `app/Views/layouts/master.php` | HTML wrapper template |

## 🔐 Security Highlights

| Feature | Implementation |
|---------|-----------------|
| **File Validation** | Whitelist extensions (PNG, JPG), verify MIME type, enforce 2MB limit |
| **CSRF Protection** | `csrf_field()` in all forms |
| **SQL Injection** | CodeIgniter Query Builder (parameterized) |
| **XSS Prevention** | `esc()` output escaping on all user data |
| **Mass Assignment** | `$allowedFields` whitelist in Model |
| **File Storage** | Randomized filenames prevent path traversal |
| **Exception Handling** | try-catch blocks with secure error logging |

## 📊 Database Schema

```sql
users
├── id (INT, PK, AUTO_INCREMENT)
├── name (VARCHAR(100), NOT NULL)
├── email (VARCHAR(100), NOT NULL, UNIQUE)
├── avatar (VARCHAR(255), NOT NULL)
├── created_at (TIMESTAMP, AUTO)
└── updated_at (TIMESTAMP, AUTO)
```

## 🛣️ API Routes

```
GET    /               → Redirect to user listing
GET    /users          → Display paginated user list with search
GET    /users/create   → Show file upload form
POST   /users/store    → Process file upload & save to DB
GET    /users/delete/:id → Delete user & avatar file
```

## 💾 Pagination & Caching

**Pagination:**
- 3 users per page (configurable in Controller line 45)
- Bootstrap-styled pagination controls
- Page parameter preserved in search results

**Caching:**
- MD5-hashed cache keys prevent collision
- 5-minute TTL (time-to-live)
- Automatic invalidation on user deletion
- Reduces database load significantly

## 🧪 Quick Test Checklist

- [ ] Visit `/users/create` → see upload form
- [ ] Try uploading .txt file → validation error
- [ ] Upload valid PNG/JPG → success message
- [ ] Check `/users` → see paginated table (max 3)
- [ ] Try search → filters by name/email
- [ ] Delete user → file removed + cache cleared
- [ ] Check `public/uploads/` → avatar files stored
- [ ] Check `writable/logs/` → debug messages

## 📁 Directory Structure

```
app/
├── Controllers/UserController.php    ← Main CRUD handler
├── Models/UserModel.php              ← Data layer
├── Views/
│   ├── users/create.php              ← Upload form
│   ├── users/index.php               ← User listing
│   └── layouts/master.php            ← Base template
└── Config/
    ├── Routes.php                    ← URL routing
    └── Database.php                  ← DB connection

public/
├── uploads/                          ← Avatar storage
└── index.php                         ← Entry point

writable/
└── logs/                             ← Debug logs
```

## 🔍 Key Code Highlights

### File Upload Validation (Lines 76-80)
```php
$rules = [
    'avatar' => 'uploaded[avatar]|max_size[avatar,2048]|
                 ext_in[avatar,png,jpg,jpeg]|
                 mime_in[avatar,image/png,image/jpeg]'
];
```

### Paginated Query (Line 45)
```php
$users = $query->paginate(3, 'default');
```

### Cache Implementation (Lines 27-61)
```php
$cacheKey = 'users_list_' . md5($searchKeyword . '_' . $currentPage);
if (! $cachedData = cache($cacheKey)) {
    // Fetch & cache
}
```

### Form Enctype (Line 20)
```html
<form method="post" enctype="multipart/form-data">
    <!-- File input REQUIRES multipart/form-data -->
    <input type="file" name="avatar">
</form>
```

## 🐛 Debugging

**Enable Debug Mode:**
Edit `.env`:
```
CI_ENVIRONMENT = development
```

**View Logs:**
```bash
tail -f writable/logs/log-*.log
```

**Common Issues:**

| Issue | Solution |
|-------|----------|
| 404 Not Found | Check routes in `app/Config/Routes.php` |
| File not uploading | Verify `public/uploads/` exists & is writable |
| Database error | Check `.env` credentials & run CREATE TABLE |
| Cache issues | Delete cache: `cache()->clean()` in delete() |
| Email not sending | Configure SMTP in `app/Config/Email.php` |

## 📚 Detailed Documentation

For comprehensive information including:
- Complete line-by-line code breakdown
- Security implementation details
- Database schema explanation
- Caching strategy deep dive
- Data flow diagrams
- Screenshots reference guide

**👉 See [DOCUMENTATION.md](./DOCUMENTATION.md)**

## 🛠️ Server Requirements

- **PHP:** 8.2+ (with intl, mbstring, json extensions)
- **Database:** MySQL 5.7+ or MariaDB 10.2+
- **Web Server:** Apache 2.4+, Nginx 1.16+, or PHP built-in server
- **Disk Space:** ~500MB (framework + dependencies)
- **Disk I/O:** Write access to `/public/uploads/` and `/writable/logs/`

## 📦 Dependencies

- **CodeIgniter 4:** Framework core
- **Bootstrap 5:** CSS framework (via CDN)
- **PHP MySQLi:** Database driver
- **Composer:** Package manager

## ✅ Project Status

- [x] File upload component ✓
- [x] Server-side validation ✓
- [x] Database integration ✓
- [x] Paginated listing ✓
- [x] Search filtering ✓
- [x] Email notifications ✓
- [x] Caching layer ✓
- [x] Security hardening ✓
- [x] Documentation ✓

## 📄 License

Open source - Use freely for educational purposes.

## 🤝 Support

For issues or questions:
1. Check [DOCUMENTATION.md](./DOCUMENTATION.md)
2. Review CodeIgniter docs: https://codeigniter.com/user_guide/
3. Check logs in `writable/logs/`

---

**Framework:** CodeIgniter 4  
**PHP Version:** 8.2+  
**Last Updated:** June 1, 2026  
**Status:** ✅ Production Ready
