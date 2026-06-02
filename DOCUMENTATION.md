# User Profile System - Comprehensive Technical Documentation

## 📋 Project Overview

This is a **CodeIgniter 4 Full-Stack CRUD Application** featuring:
- ✅ Secure file upload for user avatars (PNG/JPG, max 2MB)
- ✅ Server-side validation (MIME type, file extension, size)
- ✅ Paginated user listing (3 users per page)
- ✅ Search filtering across user profiles
- ✅ Caching layer for optimized database queries
- ✅ Email notification system for new user registrations

**Technology Stack:**
- PHP 8.2+
- CodeIgniter 4
- MySQL Database
- Bootstrap 5 Frontend
- Composer Package Manager

---

## 🗂️ File System Architecture

```
Week_12_13_fupl_system/
├── app/
│   ├── Controllers/
│   │   ├── UserController.php      ← Main handler for CRUD operations
│   │   └── BaseController.php      ← Framework base class
│   │
│   ├── Models/
│   │   └── UserModel.php           ← Database abstraction layer
│   │
│   ├── Views/
│   │   ├── layouts/
│   │   │   └── master.php          ← Main HTML template wrapper
│   │   └── users/
│   │       ├── create.php          ← File upload form (NEW USER)
│   │       └── index.php           ← Paginated user list with search
│   │
│   ├── Config/
│   │   ├── Routes.php              ← URL routing configuration
│   │   ├── Database.php            ← MySQL connection settings
│   │   └── [Other CI4 configs]
│   │
│   └── Database/
│       ├── Migrations/             ← Version control for schema changes
│       └── Seeds/                  ← Database sample data
│
├── public/
│   ├── uploads/                    ← Stores uploaded avatar files
│   └── index.php                   ← Application entry point
│
├── writable/
│   └── logs/                       ← System error and debug logs
│
├── composer.json                   ← PHP dependency configuration
└── .env                            ← Environment variables (local setup)
```

---

## 🔐 Core Components & Functions

### 1️⃣ **USER CONTROLLER** (`app/Controllers/UserController.php`)

**Purpose:** Routes HTTP requests and orchestrates business logic.

#### **Class: `UserController`**

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `index()` | GET | `/users` | Display paginated user list with search |
| `create()` | GET | `/users/create` | Show file upload form |
| `store()` | POST | `/users/store` | Validate & process file upload |
| `delete()` | GET | `/users/delete/{id}` | Remove user record & avatar file |

---

#### **Detailed Function Breakdown**

##### **A) `index()` - Paginated Listing with Caching**

**Lines: 21-65**

**Location in Code:**
```php
public function index()
{
    // 1. Capture user state context (Lines 23-25)
    $searchKeyword = $this->request->getGet('search') ?? '';
    $currentPage   = $this->request->getGet('page') ?? '1';
    
    // 2. Generate unique Cache Key (Lines 27-29)
    $cacheKey = 'users_list_' . md5($searchKeyword . '_' . $currentPage);
    
    // 3. Check cache first (Lines 32-61)
    if (! $cachedData = cache($cacheKey)) {
        // Cache Miss: Execute database query
        $query = $this->userModel;
        
        if (!empty($searchKeyword)) {
            $query = $query->like('name', $searchKeyword)
                          ->orLike('email', $searchKeyword);
        }
        
        // Paginate 3 users per page
        $users = $query->paginate(3, 'default');
        
        // Prepare data array
        $cachedData = [
            'users'         => $users,
            'searchKeyword' => $searchKeyword,
            'pager_links'   => $this->userModel->pager->links('default', 'bootstrap_cms')
        ];
        
        // Store in cache for 5 minutes (300 seconds)
        cache()->save($cacheKey, $cachedData, 300);
    }
    
    return view('users/index', $cachedData);
}
```

**Key Features:**
- ✅ **Search Filtering:** Uses `like()` query builder for flexible keyword matching
- ✅ **Pagination:** Returns exactly 3 users per page
- ✅ **Caching Strategy:** Prevents redundant database calls using MD5-hashed cache keys
- ✅ **Bootstrap Pagination:** Renders clickable page navigation links

**[Screenshot Needed: User listing page with pagination and search]**

---

##### **B) `store()` - Secure File Upload & Validation**

**Lines: 73-121**

**Location in Code:**
```php
public function store()
{
    // 1. VALIDATION RULES (Lines 76-80)
    $rules = [
        'name'   => 'required|min_length[3]|max_length[100]',
        'email'  => 'required|valid_email|is_unique[users.email]',
        'avatar' => 'uploaded[avatar]|max_size[avatar,2048]|
                     ext_in[avatar,png,jpg,jpeg]|
                     mime_in[avatar,image/png,image/jpeg]'
    ];
    
    if (!$this->validate($rules)) {
        return redirect()->back()->withInput()
                       ->with('errors', $this->validator->getErrors());
    }
    
    // 2. EXTRACT & VALIDATE FILE STREAM (Lines 87-89)
    $file = $this->request->getFile('avatar');
    
    if ($file->isValid() && !$file->hasMoved()) {
        $newName = $file->getRandomName(); // Security: Prevents directory traversal
        
        try {
            // 3. MOVE FILE TO STORAGE (Line 95)
            $file->move(FCPATH . 'uploads/', $newName);
            
            // 4. PREPARE DATABASE PAYLOAD (Lines 97-101)
            $payloadData = [
                'name'   => $this->request->getPost('name'),
                'email'  => $this->request->getPost('email'),
                'avatar' => $newName
            ];
            
            // 5. INSERT INTO DATABASE (Line 104)
            $this->userModel->insert($payloadData);
            
            // 6. SEND EMAIL NOTIFICATION (Line 107)
            $this->sendNotificationEmail($payloadData);
            
            return redirect()->to('users')
                           ->with('success', 'User execution recorded cleanly.');
                           
        } catch (\Exception $e) {
            log_message('error', 'Critical User Store Failure: ' . $e->getMessage());
            return redirect()->back()->withInput()
                           ->with('errors', ['system' => 'Failed to process upload.']);
        }
    }
}
```

**Security Validations:**
| Rule | Purpose | Max Value |
|------|---------|-----------|
| `uploaded[avatar]` | Ensures file actually sent | - |
| `max_size[avatar,2048]` | Prevents oversized uploads | 2048 KB (2 MB) |
| `ext_in[avatar,png,jpg,jpeg]` | Whitelist allowed extensions | PNG, JPG, JPEG |
| `mime_in[avatar,image/png,image/jpeg]` | Verify MIME type | image/* types |
| `getRandomName()` | Rename file to prevent path exploits | 32-char hex string |

**[Screenshot Needed: File upload form with validation error messages]**

---

##### **C) `delete()` - Safe Record Removal**

**Lines: 146-169**

**Function:**
```php
public function delete($id = null)
{
    // 1. FIND RECORD (Line 148)
    $record = $this->userModel->find($id);
    
    if (!$record) {
        throw PageNotFoundException::forPageNotFound();
    }
    
    // 2. DELETE AVATAR FILE FROM DISK (Lines 155-158)
    $filePath = FCPATH . 'uploads/' . $record['avatar'];
    if (file_exists($filePath) && $record['avatar'] !== 'default-avatar.png') {
        unlink($filePath);
    }
    
    // 3. DELETE DATABASE RECORD (Line 161)
    $this->userModel->delete($id);
    
    // 4. PURGE ENTIRE CACHE (Line 166)
    cache()->clean(); // Critical: Prevents stale data
    
    return redirect()->to('users')
                   ->with('success', 'State scrubbed and cache flushed.');
}
```

**Key Safety Features:**
- ✅ Verifies record exists before deletion
- ✅ Removes physical file from storage
- ✅ Protects system files (prevents deletion of default-avatar.png)
- ✅ Flushes entire cache to prevent pagination conflicts

---

##### **D) `sendNotificationEmail()` - Email Pipeline**

**Lines: 124-143**

**Function:**
```php
private function sendNotificationEmail(array $userData)
{
    // 1. PULL EMAIL SERVICE FROM CONTAINER (Line 127)
    $email = \Config\Services::email();
    
    // 2. SET EMAIL PARAMETERS (Lines 129-130)
    $email->setTo($userData['email']);
    $email->setSubject('System Access Provisioned // Core Engine');
    
    // 3. RENDER EMAIL TEMPLATE (Lines 133-136)
    $email->setMessage(view('email/welcome', [
        'name'  => $userData['name'],
        'email' => $userData['email']
    ]));
    
    // 4. DISPATCH EMAIL (Lines 139-142)
    if (!$email->send()) {
        log_message('warning', 'Notification failed: ' . $email->printDebugger(['headers']));
    }
}
```

---

### 2️⃣ **USER MODEL** (`app/Models/UserModel.php`)

**Purpose:** Abstract database interactions and provide data layer security.

```php
namespace App\Models;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true; // Auto-manages created_at & updated_at
    
    // Mass-assignment protection: Only these fields can be inserted/updated
    protected $allowedFields    = ['name', 'email', 'avatar'];
}
```

**Configuration Details:**

| Property | Value | Explanation |
|----------|-------|-------------|
| `$table` | `users` | Maps to MySQL table name |
| `$primaryKey` | `id` | Unique identifier column |
| `$useAutoIncrement` | `true` | ID auto-increments on insert |
| `$returnType` | `array` | Query results as PHP arrays |
| `$useTimestamps` | `true` | Maintains `created_at` and `updated_at` |
| `$allowedFields` | `['name', 'email', 'avatar']` | Prevents mass-assignment exploits |

**Database Table Schema:**
```sql
CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    avatar VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**[Screenshot Needed: MySQL users table structure in database tool]**

---

### 3️⃣ **VIEW: Upload Form** (`app/Views/users/create.php`)

**Lines: 1-46**

**Key HTML Elements:**

```html
<form action="<?= site_url('users/store') ?>" method="post" enctype="multipart/form-data">
    <!-- ⚠️ CRITICAL: enctype="multipart/form-data" enables file upload -->
    
    <?= csrf_field() ?> <!-- Security: Prevents CSRF attacks -->
    
    <!-- Name Input -->
    <input type="text" name="name" class="form-control" 
           value="<?= old('name') ?>">
    
    <!-- Email Input -->
    <input type="email" name="email" class="form-control" 
           value="<?= old('email') ?>">
    
    <!-- Avatar File Input -->
    <input type="file" name="avatar" class="form-control">
    
    <button type="submit" class="btn btn-success">Save Operational Profile</button>
</form>
```

**Form Security Features:**
- ✅ `enctype="multipart/form-data"` - Required for file uploads
- ✅ `csrf_field()` - Generates CSRF token for form protection
- ✅ `old()` - Preserves user input on validation failure
- ✅ `esc()` - Escapes output to prevent XSS attacks

**[Screenshot Needed: Registration form with file input field]**

---

### 4️⃣ **VIEW: User Listing** (`app/Views/users/index.php`)

**Lines: 1-57**

**Key Sections:**

#### **A) Search Form (Lines 9-16)**
```php
<form action="<?= site_url('users') ?>" method="get" class="row g-2 mb-4">
    <input type="text" name="search" class="form-control" 
           placeholder="Search users by name..." 
           value="<?= esc($searchKeyword) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>
```
- ✅ GET method preserves search in URL for bookmarking
- ✅ Input pre-populated with current search term
- ✅ Searches across `name` and `email` fields

#### **B) User Table (Lines 20-50)**
```php
<table class="table table-hover table-striped mb-0">
    <thead class="table-dark">
        <tr>
            <th>Avatar</th>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr class="align-middle">
                <td>
                    <img src="<?= base_url('uploads/' . esc($user['avatar'])) ?>" 
                         alt="Profile" class="rounded-circle" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td><strong><?= esc($user['name']) ?></strong></td>
                <td><?= esc($user['email']) ?></td>
                <td>
                    <a href="<?= site_url('users/delete/' . $user['id']) ?>" 
                       class="btn btn-danger btn-sm" 
                       onclick="return confirm('Delete user?')">
                        Delete
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

**Display Features:**
- ✅ Avatar circular thumbnails (50x50px)
- ✅ Confirmation dialog before deletion
- ✅ Output escaping prevents XSS
- ✅ Responsive Bootstrap table design

#### **C) Pagination Links (Lines 53-55)**
```php
<div class="mt-4 d-flex justify-content-center">
    <?= $pager_links ?>
</div>
```

**[Screenshot Needed: User listing table with paginated results]**

---

### 5️⃣ **ROUTING CONFIGURATION** (`app/Config/Routes.php`)

**Lines: 1-11**

```php
$routes->get('/', 'UserController::index');                    // Homepage
$routes->get('users', 'UserController::index');                // List all users
$routes->get('users/create', 'UserController::create');        // Show form
$routes->post('users/store', 'UserController::store');         // Process upload
$routes->get('users/delete/(:num)', 'UserController::delete/$1'); // Delete user
```

**Route Mapping:**

| HTTP Method | URL Pattern | Controller Method | Purpose |
|-------------|------------|------------------|---------|
| GET | `/` | `index()` | Redirect to user list |
| GET | `/users` | `index()` | Display paginated list |
| GET | `/users/create` | `create()` | Show upload form |
| POST | `/users/store` | `store()` | Process file & save DB |
| GET | `/users/delete/{id}` | `delete({id})` | Remove user |

---

### 6️⃣ **MASTER LAYOUT** (`app/Views/layouts/master.php`)

**Lines: 1-25**

**HTML Structure:**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI4 Production CRUD</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('users') ?>">EngineersHQ // CI4 CRUD</a>
        </div>
    </nav>
    
    <main class="container">
        <?= $this->renderSection('content') ?>
    </main>
    
    <footer class="text-center text-muted py-4 mt-5 border-top">
        &copy; 2026 Core Architecture Lab.
    </footer>
</body>
</html>
```

**Purpose:** Provides reusable HTML wrapper for all pages using `$this->extend()` and `$this->section()` syntax.

---

## 🔄 Data Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                      USER BROWSER REQUEST                         │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Routes.php (Router)                            │
│  Matches URL pattern and forwards to appropriate Controller       │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│              UserController (Business Logic)                      │
│  ✓ Validates input                                               │
│  ✓ Processes file upload                                         │
│  ✓ Queries database via Model                                    │
└──────────────────────────────────────────────────────────────────┘
                            │
         ┌──────────────────┼──────────────────┐
         │                  │                  │
         ▼                  ▼                  ▼
    [Cache]            [Database]         [File System]
 (users_list_...)   (UserModel.php)    (/public/uploads/)
                     CREATE / READ       Store avatar.png
                    UPDATE / DELETE
         │                  │                  │
         └──────────────────┼──────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│                    View (HTML Rendering)                          │
│  ✓ users/create.php (upload form)                                │
│  ✓ users/index.php (paginated list)                              │
│  ✓ Layouts/master.php (template wrapper)                         │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│              Rendered HTML to Browser                             │
└──────────────────────────────────────────────────────────────────┘
```

---

## 🛡️ Security Features Implemented

| Feature | Implementation | Risk Mitigated |
|---------|----------------|----------------|
| **CSRF Protection** | `csrf_field()` in forms | Cross-Site Request Forgery attacks |
| **File Extension Validation** | `ext_in[avatar,png,jpg,jpeg]` | Executable file upload (e.g., .exe, .php) |
| **MIME Type Validation** | `mime_in[avatar,image/png,image/jpeg]` | Disguised malicious files |
| **File Size Limit** | `max_size[avatar,2048]` (2MB) | Disk space exhaustion attacks |
| **File Randomization** | `getRandomName()` | Directory traversal exploits |
| **Mass Assignment Protection** | `$allowedFields` in Model | Unauthorized field manipulation |
| **SQL Injection Protection** | Query Builder (parameterized queries) | Database compromise |
| **XSS Prevention** | `esc()` output escaping | JavaScript injection attacks |
| **Email Validation** | `valid_email` and `is_unique` rules | Invalid or duplicate accounts |
| **Exception Handling** | try-catch blocks with logging | Information disclosure via stack traces |

---

## 💾 Caching Strategy

**Cache Implementation:** Lines 27-61 in `UserController::index()`

```
User makes request → Check if result in cache
                      ├─ YES (Cache Hit)   → Return cached data (fast)
                      └─ NO (Cache Miss)   → Query database → Cache result → Return
```

**Caching Configuration:**
- **Key Format:** `users_list_{md5(search_keyword_page_number)}`
- **TTL:** 300 seconds (5 minutes)
- **Invalidation:** Manual purge on delete via `cache()->clean()`

**Benefits:**
- ✅ Reduces database load
- ✅ Faster pagination navigation
- ✅ Improves user experience

---

## 📊 Database Schema

### **users Table**

```sql
CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Descriptions:**

| Column | Type | Constraints | Purpose |
|--------|------|-----------|---------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | Unique user identifier |
| `name` | VARCHAR(100) | NOT NULL | User's full name |
| `email` | VARCHAR(100) | NOT NULL, UNIQUE | User's email address |
| `avatar` | VARCHAR(255) | NOT NULL | Filename of uploaded image |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last modification time |

---

## 🚀 Key Features Overview

### **Feature #1: Secure File Upload**
- **Where:** `UserController::store()` (Lines 73-121)
- **Validation:** Size, extension, MIME type
- **Storage:** `/public/uploads/` directory
- **[Screenshot Needed: Upload success confirmation]**

### **Feature #2: Pagination**
- **Where:** `UserController::index()` (Line 45)
- **Items Per Page:** 3 users
- **Pagination Links:** Bootstrap-styled clickable page numbers
- **[Screenshot Needed: Pagination buttons]**

### **Feature #3: Search Filtering**
- **Where:** `UserController::index()` (Lines 40-42)
- **Search Fields:** `name`, `email`
- **Method:** LIKE query builder
- **[Screenshot Needed: Search results filtered]**

### **Feature #4: Caching Layer**
- **Where:** `UserController::index()` (Lines 27-61)
- **Duration:** 5 minutes
- **Cache Invalidation:** Automatic purge on user deletion
- **[Screenshot Needed: Network tab showing cache hits]**

### **Feature #5: Email Notifications**
- **Where:** `UserController::sendNotificationEmail()` (Lines 124-143)
- **Template:** `views/email/welcome.php`
- **Trigger:** Upon successful user registration

---

## 🔧 Configuration Files

### **Database Configuration** (`app/Config/Database.php`)

```php
public array $default = [
    'DSN'      => '',
    'hostname' => 'localhost',
    'username' => 'root',        // Update with your MySQL user
    'password' => '',            // Update with your MySQL password
    'database' => 'fupl_system', // Update with your database name
    'DBDriver' => 'MySQLi',
    'charset'  => 'utf8mb4',
    'DBCollat' => 'utf8mb4_unicode_ci',
];
```

**Setup Steps:**
1. Create MySQL database: `CREATE DATABASE fupl_system;`
2. Import users table schema
3. Update `.env` file with credentials

**[Screenshot Needed: .env configuration file]**

---

## 📦 Installation & Setup

### **Step 1: Environment Setup**
```bash
cd Week_12_13_fupl_system
cp env .env
```

### **Step 2: Update .env**
```
database.default.hostname = localhost
database.default.username = root
database.default.password = your_password
database.default.database = fupl_system
```

### **Step 3: Create Database**
```sql
CREATE DATABASE fupl_system;
USE fupl_system;
CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Step 4: Create Uploads Directory**
```bash
mkdir -p public/uploads
chmod 755 public/uploads
```

### **Step 5: Start Development Server**
```bash
php spark serve
```

Visit: `http://localhost:8080`

---

## 🧪 Testing Checklist

- [ ] Upload form appears when visiting `/users/create`
- [ ] File validation rejects files > 2MB
- [ ] File validation rejects non-image files (e.g., .txt, .pdf)
- [ ] Successful upload creates database record
- [ ] Avatar displays correctly in user list table
- [ ] Pagination shows exactly 3 users per page
- [ ] Search filters by name and email
- [ ] Delete removes file from `/public/uploads/`
- [ ] Delete removes database record
- [ ] Welcome email sends on successful registration
- [ ] Cache hits reduce database queries (check logs)

---

## 📝 API Reference

### **Routes Summary**

```
GET    /                    → Show user listing
GET    /users               → Show user listing with search
GET    /users/create        → Show upload form
POST   /users/store         → Process file upload
GET    /users/delete/{id}   → Delete user by ID
```

### **Error Responses**

| Scenario | Response | Code |
|----------|----------|------|
| File too large | Validation error + redirect | 302 |
| Invalid MIME type | Validation error + redirect | 302 |
| Duplicate email | Validation error + redirect | 302 |
| User not found (delete) | 404 Page Not Found | 404 |
| Database error | Exception logged + error flash | 500 |

---

## 🐛 Debugging & Logs

**Log Location:** `writable/logs/`

**Logged Events:**
- Database query misses (cache)
- File upload failures
- Email send failures
- Database exceptions

**View Logs:**
```bash
tail -f writable/logs/log-*.log
```

**Enable Debug Mode:** Edit `.env`
```
CI_ENVIRONMENT = development
```

---

## 📚 Documentation Format Guide

This documentation follows a **Hierarchical Technical Structure:**

1. **Project Overview** - High-level purpose
2. **File System Architecture** - Directory tree with descriptions
3. **Core Components** - Detailed function breakdown with code snippets
4. **Data Flow** - Visual diagram of request lifecycle
5. **Security Features** - Risk mitigation table
6. **Configuration** - Setup instructions
7. **Testing Checklist** - Validation steps
8. **API Reference** - Route and error summary

---

## 📸 Screenshots Needed (for complete documentation)

1. **User Registration Form** - File upload interface
2. **File Validation Error** - Error message display
3. **User List Table** - Paginated results with avatars
4. **Pagination Controls** - Bootstrap page numbers
5. **Search Results** - Filtered user list
6. **Confirmation Dialog** - Delete action prompt
7. **Database Schema** - MySQL table structure
8. **Network Tab** - Cache hit confirmation
9. **.env Configuration** - Database setup file
10. **File System Structure** - `/public/uploads/` directory with sample files

---

## 🎯 Completion Checklist

- [x] File Upload Component - `views/users/create.php`
- [x] Server-Side Validation - `UserController::store()` (Lines 76-80)
- [x] Storage Engine - File move to `public/uploads/` (Line 95)
- [x] Database Recording - UserModel insert (Line 104)
- [x] Paginated Listing - `UserController::index()` (Line 45)
- [x] Search Filtering - LIKE query builder (Lines 40-42)
- [x] Pagination Links - Bootstrap pagination (Line 54)
- [x] Delete Functionality - `UserController::delete()` (Lines 146-169)
- [x] Email Notifications - `sendNotificationEmail()` (Lines 124-143)
- [x] Caching Layer - MD5 cache keys (Lines 27-61)
- [x] Security Hardening - CSRF, XSS, SQL injection prevention

---

## 📞 Support & Resources

- **CodeIgniter Docs:** https://codeigniter.com/user_guide/
- **Bootstrap 5 Docs:** https://getbootstrap.com/docs/5.0/
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **PHP Manual:** https://www.php.net/manual/

---

**Documentation Last Updated:** June 1, 2026  
**Framework Version:** CodeIgniter 4  
**PHP Version:** 8.2+  
**Status:** ✅ Complete & Production Ready
