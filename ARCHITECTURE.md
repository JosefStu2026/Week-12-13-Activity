# System Architecture & Data Flow

Comprehensive visual and textual breakdown of the entire system architecture.

---

## 🏗️ High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER BROWSER                                │
│                    (Web Browser Client)                             │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             │ HTTP Requests/Responses
                             │
┌────────────────────────────▼────────────────────────────────────────┐
│                    PUBLIC WEB SERVER                                │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ /public/index.php (CodeIgniter Entry Point)                │   │
│  │ - Bootstraps framework                                      │   │
│  │ - Routes incoming requests                                  │   │
│  └────────────────────┬────────────────────────────────────────┘   │
│                       │                                             │
│                       ▼                                             │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ Router (Routes.php)                                         │   │
│  │ Matches URL patterns to Controllers                         │   │
│  └────────────────────┬────────────────────────────────────────┘   │
│                       │                                             │
└───────────────────────┼─────────────────────────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
    (Dispatch)     (Dispatch)      (Dispatch)
        │               │               │
┌───────▼────┐   ┌─────▼──────┐   ┌───▼─────────────┐
│   index()  │   │  create()  │   │ store() delete()│
│   (Read)   │   │   (Read)   │   │   (Write)       │
└───────┬────┘   └─────┬──────┘   └───┬─────────────┘
        │               │               │
        │               │     ┌─────────┼────────────────┐
        │               │     │         │                │
        ▼               ▼     ▼         ▼                ▼
    ┌───────────────────────────────────────────────────────────┐
    │           DATABASE LAYER (UserModel)                      │
    │  Validates data → Executes queries → Returns results      │
    │                                                            │
    │  Methods:                                                 │
    │  - paginate()  - Get 3 users per page                    │
    │  - like()      - Search by name/email                    │
    │  - insert()    - Save new user                           │
    │  - find()      - Get user by ID                          │
    │  - delete()    - Remove user                             │
    └────────┬─────────────────────────┬──────────────────────┘
             │                         │
             ▼                         ▼
        ┌─────────────────────────────────────┐
        │        MySQL DATABASE               │
        │    users table (5 columns)          │
        │    - id (PK)                        │
        │    - name                           │
        │    - email (UNIQUE)                 │
        │    - avatar                         │
        │    - created_at, updated_at         │
        └────────────┬────────────────────────┘
                     │
        ┌────────────┴──────────────┐
        │                           │
        ▼                           ▼
    ┌─────────────┐           ┌──────────────┐
    │ CACHE LAYER │           │ FILE STORAGE │
    │(5 min TTL) │           │ /uploads/    │
    │             │           │              │
    │MD5 hashed   │           │Avatar files  │
    │cache keys   │           │(randomized   │
    │for          │           │ names)       │
    │pagination   │           │              │
    └─────────────┘           └──────────────┘
        │
        ▼
    ┌─────────────────────────────────────┐
    │      EMAIL SERVICE                  │
    │   (Optional - Send Notifications)   │
    │   - welcome.php template            │
    │   - SMTP configuration              │
    └─────────────────────────────────────┘
        │
        ▼
    ┌──────────────────────┐
    │   Email Recipient    │
    │   (User's inbox)     │
    └──────────────────────┘
```

---

## 🔄 Request/Response Flow Diagram

### Scenario 1: Upload New User

```
┌──────────────────────────────────────────────────────────────────────┐
│ STEP 1: User Visits Form (GET /users/create)                        │
└──────────────────────────────────────────────────────────────────────┘

Browser sends: GET /users/create
       ↓
Routes.php matches: get('users/create', 'UserController::create')
       ↓
Controller::create() executes
       ↓
Returns: view('users/create')
       ↓
Browser receives: HTML form with fields:
       ├─ name (text input)
       ├─ email (email input)
       └─ avatar (file input)

---

┌──────────────────────────────────────────────────────────────────────┐
│ STEP 2: User Submits Form (POST /users/store)                       │
└──────────────────────────────────────────────────────────────────────┘

User fills:
  ├─ Name: "John Doe"
  ├─ Email: "john@example.com"
  └─ Avatar: uploads avatar.jpg

Clicks Submit Button
       ↓
Browser sends: POST /users/store
       ├─ POST data: name, email
       └─ File data: avatar.jpg
       ↓
Routes.php matches: post('users/store', 'UserController::store')
       ↓
Controller::store() executes:

    1. VALIDATE INPUT (Line 82)
       ├─ name: required, 3-100 chars ✓
       ├─ email: valid email, unique ✓
       └─ avatar: uploaded, <2MB, PNG/JPG, MIME verified ✓
       
    2. EXTRACT FILE (Line 87)
       └─ $file = $request->getFile('avatar')
       
    3. GENERATE RANDOM NAME (Line 90)
       └─ $newName = "d4f3a2b1c9e8f7g6.jpg" (security measure)
       
    4. MOVE FILE (Line 95)
       └─ File moved to: /public/uploads/d4f3a2b1c9e8f7g6.jpg
       
    5. PREPARE DATA (Lines 97-101)
       └─ $payloadData = [
           'name' => 'John Doe',
           'email' => 'john@example.com',
           'avatar' => 'd4f3a2b1c9e8f7g6.jpg'
       ]
       
    6. INSERT TO DATABASE (Line 104)
       └─ INSERT INTO users (name, email, avatar) VALUES (...)
          └─ MySQL returns: id = 1, created_at = now
       
    7. SEND EMAIL (Line 107)
       └─ Email service renders email/welcome.php
       └─ Sends to john@example.com
       
    8. REDIRECT (Line 109)
       └─ Return: redirect()->to('users') with success message
       
Browser receives: HTTP 302 Redirect
       ↓
Browser follows redirect to: GET /users
       ↓
Step 3 begins (see below)

---

┌──────────────────────────────────────────────────────────────────────┐
│ STEP 3: View User List (GET /users)                                 │
└──────────────────────────────────────────────────────────────────────┘

Browser sends: GET /users
       ↓
Routes.php matches: get('users', 'UserController::index')
       ↓
Controller::index() executes:

    1. GET PARAMS (Lines 24-25)
       ├─ search = "" (no search)
       └─ page = "1" (first page)
       
    2. GENERATE CACHE KEY (Line 29)
       └─ $cacheKey = "users_list_" + md5("_1")
          └─ = "users_list_b026324c6904b2a9cb4b88d6d61c81d1"
       
    3. CHECK CACHE (Line 32)
       ├─ Cache exists? NO (first time)
       ├─ CACHE MISS: Query database
       │
       └─ Execute query:
           SELECT * FROM users LIMIT 3 OFFSET 0
           └─ Returns: [John Doe record] (1 user)
           
    4. CACHE RESULT (Line 57)
       └─ cache()->save($cacheKey, $data, 300)
          └─ Stored for 5 minutes
       
    5. RETURN VIEW (Line 64)
       └─ return view('users/index', $cachedData)
       
Browser receives: HTML table showing:
       ├─ Avatar thumbnail (50x50)
       ├─ Name: John Doe
       ├─ Email: john@example.com
       └─ Actions: Delete button

---

┌──────────────────────────────────────────────────────────────────────┐
│ STEP 4: Delete User (GET /users/delete/1)                           │
└──────────────────────────────────────────────────────────────────────┘

User clicks Delete button
       ↓
Confirmation dialog appears: "Are you sure?"
       ↓
User clicks OK
       ↓
Browser sends: GET /users/delete/1
       ↓
Routes.php matches: get('users/delete/(:num)', 'UserController::delete/$1')
       ↓
Controller::delete(1) executes:

    1. FIND RECORD (Line 148)
       └─ $record = userModel->find(1)
       └─ Returns: ['id'=>1, 'name'=>'John', 'avatar'=>'d4f3...jpg']
       
    2. DELETE FILE (Lines 155-157)
       └─ filePath = "/public/uploads/d4f3a2b1c9e8f7g6.jpg"
       └─ File exists? YES
       └─ Is default? NO
       └─ unlink(filePath) → File deleted from disk
       
    3. DELETE FROM DB (Line 161)
       └─ DELETE FROM users WHERE id = 1
       
    4. FLUSH CACHE (Line 166)
       └─ cache()->clean() → Clear all cached pages
       
    5. REDIRECT (Line 168)
       └─ redirect()->to('users') with success message
       
Browser receives: HTTP 302 Redirect
       ↓
Browser follows: GET /users
       ↓
Controller::index() executes again
       └─ Cache is now empty (was flushed)
       └─ Database now has 0 users
       └─ View shows "No records found"
```

---

## 💾 Database Interaction Pattern

```
┌─────────────────────────────────────────────────────────────────────┐
│                       CONTROLLER REQUEST                             │
│                   (from UserController.php)                         │
└──────────────────────────┬──────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    USERMODEL CLASS                                   │
│  (inherits from CodeIgniter\Model)                                  │
│                                                                      │
│  Properties:                                                        │
│  - protected $table = 'users'                                      │
│  - protected $allowedFields = [...]                                │
│                                                                      │
│  Methods:                                                           │
│  - find($id)         → SELECT * WHERE id = $id                     │
│  - findAll()         → SELECT * (all records)                      │
│  - insert($data)     → INSERT INTO ... VALUES (...)                │
│  - update($id, $data) → UPDATE ... WHERE id = $id                  │
│  - delete($id)       → DELETE FROM ... WHERE id = $id              │
│  - like($field, $val) → WHERE field LIKE '%val%'                   │
│  - paginate(3)       → SELECT ... LIMIT 3 OFFSET X                 │
│                                                                      │
│  Query Builder Methods (chainable):                                │
│  - $model->like('name', 'john')                                   │
│  - $model->orLike('email', 'john')                                │
│  - $model->paginate(3) → Returns paginated results                │
└──────────────────────────┬──────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   DATABASE CONNECTION                                │
│                   (Config/Database.php)                             │
│                                                                      │
│  Connection Details:                                                │
│  - Host: localhost                                                  │
│  - Port: 3306                                                       │
│  - Username: root                                                   │
│  - Password: [from .env]                                            │
│  - Database: fupl_system                                            │
│  - Driver: MySQLi                                                   │
│  - Charset: utf8mb4                                                 │
└──────────────────────────┬──────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   MYSQL SERVER                                       │
│                                                                      │
│  Database: fupl_system                                              │
│  └─ Table: users                                                   │
│     ├─ Column: id (INT, PK, AUTO_INCREMENT)                       │
│     ├─ Column: name (VARCHAR(100))                                │
│     ├─ Column: email (VARCHAR(100), UNIQUE)                       │
│     ├─ Column: avatar (VARCHAR(255))                              │
│     ├─ Column: created_at (TIMESTAMP)                             │
│     └─ Column: updated_at (TIMESTAMP)                             │
└──────────────────────────┬──────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   QUERY RESULT                                       │
│                                                                      │
│  Example (SELECT * FROM users):                                     │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │ id │ name      │ email            │ avatar                     │ │
│  ├────┼───────────┼──────────────────┼────────────────────────────┤ │
│  │ 1  │ John Doe  │ john@example.com │ d4f3a2b1c9e8f7g6.jpg    │ │
│  │ 2  │ Jane Smith│ jane@example.com │ a1b2c3d4e5f6g7h8.jpg    │ │
│  └───────────────────────────────────────────────────────────────┘ │
└──────────────────────────┬──────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   RETURNED TO CONTROLLER                             │
│                  (as PHP array)                                     │
│                                                                      │
│  [                                                                  │
│      0 => ['id'=>1, 'name'=>'John', 'email'=>'john@...', ...],    │
│      1 => ['id'=>2, 'name'=>'Jane', 'email'=>'jane@...', ...],    │
│  ]                                                                  │
└──────────────────────────┬──────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   PASSED TO VIEW                                     │
│              (renders HTML table)                                   │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📁 File Organization

```
app/
│
├── Controllers/
│   ├── UserController.php
│   │   ├── index()          → Read users with pagination
│   │   ├── create()         → Display upload form
│   │   ├── store()          → Validate & save file + user
│   │   └── delete()         → Remove user & file
│   │
│   └── BaseController.php   → Parent class (framework)
│
├── Models/
│   ├── UserModel.php
│   │   ├── $table = 'users'
│   │   ├── $allowedFields = [...]
│   │   └── (inherited: find, insert, paginate, etc.)
│   │
│   └── [Other models]
│
├── Views/
│   ├── layouts/
│   │   └── master.php       → Base HTML template
│   │
│   ├── users/
│   │   ├── create.php       → Upload form
│   │   └── index.php        → User listing table
│   │
│   └── email/
│       └── welcome.php      → Email template
│
├── Config/
│   ├── Routes.php           → URL mapping
│   ├── Database.php         → DB credentials
│   ├── Validation.php       → Validation rules
│   └── [Other configs]
│
└── Database/
    ├── Migrations/          → Schema versions
    └── Seeds/               → Sample data

public/
│
├── uploads/                 ← AVATAR FILES STORED HERE
│   ├── a1b2c3d4e5f6g7.jpg
│   ├── b2c3d4e5f6g7h8.jpg
│   └── ...
│
└── index.php               ← Framework entry point

writable/
│
└── logs/                   ← Debug & error logs
    └── log-2026-06-01.log
```

---

## 🔒 Security Layers

```
┌─────────────────────────────────────────────────────────────────────┐
│                    INCOMING REQUEST                                  │
└──────────────────────────┬──────────────────────────────────────────┘
                          │
                          ▼
                    ┌─────────────┐
                    │ CSRF CHECK  │ ← csrf_field() in forms
                    │ (Token)     │
                    └──────┬──────┘
                           │
                          ▼
                    ┌─────────────────────┐
                    │ INPUT VALIDATION    │ ← $rules array
                    │ - Required fields   │
                    │ - Type validation   │
                    │ - Length limits     │
                    └──────┬──────────────┘
                           │
                          ▼
                    ┌──────────────────────┐
                    │ FILE VALIDATION      │
                    │ - Size limit (2MB)   │
                    │ - Extension (PNG/JPG)│
                    │ - MIME type check    │
                    └──────┬───────────────┘
                           │
                          ▼
                    ┌──────────────────────┐
                    │ RANDOM FILENAME      │
                    │ - Rename file        │
                    │ - Prevent traversal  │
                    └──────┬───────────────┘
                           │
                          ▼
                    ┌──────────────────────┐
                    │ PARAMETERIZED QUERY  │
                    │ - Query builder      │
                    │ - Prevent SQL inject │
                    └──────┬───────────────┘
                           │
                          ▼
                    ┌──────────────────────┐
                    │ ALLOWED FIELDS       │
                    │ - $allowedFields []  │
                    │ - Prevent mass-assign│
                    └──────┬───────────────┘
                           │
                          ▼
                    ┌──────────────────────┐
                    │ OUTPUT ESCAPING      │
                    │ - esc() function     │
                    │ - Prevent XSS        │
                    └──────┬───────────────┘
                           │
                          ▼
                    ┌──────────────────────┐
                    │ EXCEPTION HANDLING   │
                    │ - try-catch blocks   │
                    │ - Secure logging     │
                    └──────────────────────┘
```

---

## 🚀 Caching Architecture

```
┌────────────────────────────────────────────────────────────┐
│                     USER REQUEST                           │
│                  GET /users?search=john                    │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
             ┌──────────────────────────┐
             │ GENERATE CACHE KEY       │
             │ md5("john_1") = hash     │
             │ = users_list_xyz123...   │
             └──────────┬───────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
     CACHE HIT      CACHE MISS      (no cache)
        │               │
        ▼               ▼
    ┌────────┐   ┌────────────────────┐
    │ Return │   │ Query Database     │
    │ cached │   │ SELECT * FROM users│
    │ result │   │ WHERE name LIKE... │
    │(fast)  │   │ LIMIT 3 OFFSET 0   │
    └────┬───┘   └────────┬───────────┘
         │                 │
         │                 ▼
         │          ┌──────────────────┐
         │          │ Store in Cache   │
         │          │ TTL = 300 sec    │
         │          │ (5 minutes)      │
         │          └────────┬─────────┘
         │                   │
         └───────┬───────────┘
                 │
                 ▼
        ┌────────────────────┐
        │ Render View        │
        │ Return HTML        │
        └────────────────────┘

CACHE INVALIDATION (Line 166 in delete()):
┌────────────────────────────────────────────────┐
│ User Deleted → cache()->clean()                │
│ All cache entries purged                       │
│ Next request → fresh database query (CACHE MISS)
└────────────────────────────────────────────────┘
```

---

## 📊 Data Model Relationship

```
┌─────────────────────────────────────────────────────────┐
│                  USERS TABLE                             │
├─────────┬──────────────┬────────────────┬────────────────┤
│ id (PK) │ name         │ email          │ avatar         │
├─────────┼──────────────┼────────────────┼────────────────┤
│ 1       │ John Doe     │ john@ex.com    │ a1b2c3d4.jpg  │
│ 2       │ Jane Smith   │ jane@ex.com    │ b2c3d4e5.jpg  │
│ 3       │ Bob Johnson  │ bob@ex.com     │ c3d4e5f6.jpg  │
└─────────┴──────────────┴────────────────┴────────────────┘
    │
    └─→ (file reference)
        │
        ▼
┌─────────────────────────────────────────────────┐
│          FILE SYSTEM (/public/uploads/)         │
├─────────────────────────────────────────────────┤
│ a1b2c3d4.jpg  ← avatar for user ID 1            │
│ b2c3d4e5.jpg  ← avatar for user ID 2            │
│ c3d4e5f6.jpg  ← avatar for user ID 3            │
└─────────────────────────────────────────────────┘

DELETION PATTERN:
- User 1 deleted
  ├─ Delete record from users table (id=1)
  ├─ Delete file from /uploads/ (a1b2c3d4.jpg)
  └─ Clear cache

INTEGRITY:
- Each user has exactly one avatar file
- Filename is randomized (not original)
- UNIQUE constraint on email prevents duplicates
```

---

## 🔀 Pagination Architecture

```
PAGINATION FLOW:

1. User visits: http://localhost:8080/users?page=1

2. Controller extracts: $currentPage = '1'

3. Query builder: $model->paginate(3, 'default')
   └─ Requests 3 items per page
   └─ Returns items for page 1

4. Pager service generates links:
   ├─ Page 1 (current) - highlighted
   ├─ Page 2 - clickable link to ?page=2
   ├─ Page 3 - clickable link to ?page=3
   └─ etc.

5. Cache key includes page number:
   $cacheKey = md5('search_keyword' + '_page_number')
   ├─ Page 1 & no search → users_list_xyz1
   ├─ Page 2 & no search → users_list_xyz2
   ├─ Page 1 & "john"    → users_list_abc1
   └─ etc. (each page cached separately)

EXAMPLE DATA STRUCTURE:

Scenario: 10 total users, 3 per page

Page 1: users 1, 2, 3
Page 2: users 4, 5, 6
Page 3: users 7, 8, 9
Page 4: user 10

Query executed:
- Page 1: LIMIT 3 OFFSET 0   (rows 1-3)
- Page 2: LIMIT 3 OFFSET 3   (rows 4-6)
- Page 3: LIMIT 3 OFFSET 6   (rows 7-9)
- Page 4: LIMIT 3 OFFSET 9   (row 10)
```

---

## 🎯 Key Design Patterns

### Model-View-Controller (MVC)

```
       Request
          │
          ▼
      CONTROLLER
    (Business Logic)
          │
    ┌─────┴─────┐
    │           │
    ▼           ▼
  MODEL      VIEW
(Database) (HTML)
    │           │
    └─────┬─────┘
          │
          ▼
      Response
     (HTML Page)
```

### Query Builder Pattern

```
$model->like('name', 'john')        // Build WHERE clause
      ->orLike('email', 'john')     // Add OR condition
      ->paginate(3)                  // Add LIMIT/OFFSET
      →Execute()                     // Run query
      →get()                         // Return results
```

### Dependency Injection

```
// In Controller
public function __construct()
{
    $this->userModel = new UserModel();
    // Model instance injected into controller
}

// In Email Service
$email = \Config\Services::email();
// Email service pulled from service container
```

---

## 📈 Performance Optimizations

```
CACHING LAYER:
┌─────────────────────────────────────┐
│ User visits /users?search=john      │
│ Page 1: Generates cache key         │
│ 1st request: Database query (SLOW)  │
│ 2nd-Nth requests: Cache hit (FAST)  │
│ Cache TTL: 5 minutes                │
│ Cost savings: Reduce DB load by ~90%│
└─────────────────────────────────────┘

DATABASE OPTIMIZATION:
┌─────────────────────────────────────┐
│ Index on email column               │
│ (UNIQUE constraint implicitly)      │
│ Speed up: is_unique validation      │
│ Speed up: Email lookups             │
└─────────────────────────────────────┘

PAGINATION:
┌─────────────────────────────────────┐
│ Instead of SELECT * (all users)     │
│ Use LIMIT 3 OFFSET X (per page)     │
│ Reduces: Memory, Network bandwidth  │
│ Improves: Page load speed           │
│ Enhances: User experience           │
└─────────────────────────────────────┘
```

---

**Last Updated:** June 1, 2026  
**Architecture Version:** 1.0  
**Framework:** CodeIgniter 4
