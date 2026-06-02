# Code Reference - Function Locations & Explanations

Quick lookup guide for all important functions and their locations in the codebase.

---

## 🎯 Controller Functions

### File: `app/Controllers/UserController.php`

#### 1. `__construct()` - Lines 14-18

**Purpose:** Initialize the controller and instantiate UserModel.

```php
public function __construct()
{
    $this->userModel = new UserModel();
}
```

**What It Does:**
- Runs every time the controller is accessed
- Creates instance of `UserModel` for database operations
- Makes `$this->userModel` available to all methods

**Why Important:** Enables dependency injection pattern for testability.

---

#### 2. `index()` - Lines 20-65

**Purpose:** Display paginated user list with search filtering and caching.

```php
public function index()
{
    // Capture search & page params
    $searchKeyword = $this->request->getGet('search') ?? '';
    $currentPage = $this->request->getGet('page') ?? '1';
    
    // Generate unique cache key
    $cacheKey = 'users_list_' . md5($searchKeyword . '_' . $currentPage);
    
    // Check cache
    if (! $cachedData = cache($cacheKey)) {
        // Cache miss - query database
        // Apply search filter if provided
        // Paginate results (3 per page)
        // Cache the result for 300 seconds
    }
    
    return view('users/index', $cachedData);
}
```

**Key Line Numbers:**
- **Line 24:** Get search keyword from URL query string
- **Line 25:** Get current page number
- **Line 29:** Generate MD5 hash for unique cache key
- **Line 32:** Check if data exists in cache
- **Line 41:** Apply LIKE filter to name AND email
- **Line 45:** Paginate query (3 users per page)
- **Line 53:** Serialize pager links to HTML
- **Line 57:** Save to cache with 300-second TTL
- **Line 64:** Return view with data

**Related Methods:**
- Depends on: `UserModel::paginate()`
- Calls: `cache()`, `view()`
- Data Passed to View:** `users`, `searchKeyword`, `pager_links`

---

#### 3. `create()` - Lines 67-70

**Purpose:** Display the user registration/upload form.

```php
public function create()
{
    return view('users/create');
}
```

**What It Does:**
- Simple pass-through method
- Returns the upload form view
- No validation or logic here

**When It's Called:**
- GET request to `/users/create`
- User clicks "Register New User" button

---

#### 4. `store()` - Lines 73-121

**Purpose:** Validate user input, handle file upload, save to database, send email.

```php
public function store()
{
    // 1. Define validation rules
    $rules = [
        'name' => 'required|min_length[3]|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email]',
        'avatar' => 'uploaded[avatar]|max_size[avatar,2048]|
                     ext_in[avatar,png,jpg,jpeg]|
                     mime_in[avatar,image/png,image/jpeg]'
    ];
    
    // 2. Check if validation passes
    if (!$this->validate($rules)) {
        return redirect()->back()->withInput()
                       ->with('errors', $this->validator->getErrors());
    }
    
    // 3. Get file from request
    $file = $this->request->getFile('avatar');
    
    // 4. Check if file is valid
    if ($file->isValid() && !$file->hasMoved()) {
        $newName = $file->getRandomName();
        
        try {
            // 5. Move file to public/uploads/
            $file->move(FCPATH . 'uploads/', $newName);
            
            // 6. Prepare data array
            $payloadData = [
                'name' => $this->request->getPost('name'),
                'email' => $this->request->getPost('email'),
                'avatar' => $newName
            ];
            
            // 7. Insert into database
            $this->userModel->insert($payloadData);
            
            // 8. Send welcome email
            $this->sendNotificationEmail($payloadData);
            
            return redirect()->to('users')
                           ->with('success', 'User execution recorded cleanly.');
        } catch (\Exception $e) {
            // Log error and show friendly message
            log_message('error', 'Critical User Store Failure: ' . $e->getMessage());
            return redirect()->back()->withInput()
                           ->with('errors', ['system' => 'Failed to process.']);
        }
    }
    
    return redirect()->back()->withInput()
                   ->with('errors', ['avatar' => 'Target file invalid.']);
}
```

**Key Line Numbers & Functions:**

| Line | Function | Purpose |
|------|----------|---------|
| 76-80 | Validation rules | Define input constraints |
| 82-84 | `$this->validate()` | Check rules against input |
| 87 | `getFile()` | Extract file from request |
| 89 | `isValid()` | Verify file is valid |
| 90 | `getRandomName()` | Generate random filename (security) |
| 95 | `move()` | Copy file to storage |
| 97-101 | Payload array | Prepare data for DB insert |
| 104 | `insert()` | Save to database |
| 107 | `sendNotificationEmail()` | Trigger email pipeline |
| 111 | `catch` | Exception handling |
| 113 | `log_message()` | Log errors to file |

**Validation Rules Breakdown:**

```
name:
  ✓ required           - Field must be present
  ✓ min_length[3]     - At least 3 characters
  ✓ max_length[100]   - No more than 100 characters

email:
  ✓ required          - Field must be present
  ✓ valid_email       - Must be valid email format
  ✓ is_unique[users.email] - Email not already in database

avatar:
  ✓ uploaded[avatar]  - File must be uploaded (not missing)
  ✓ max_size[avatar,2048] - File ≤ 2MB (2048 KB)
  ✓ ext_in[avatar,png,jpg,jpeg] - Extension must be PNG/JPG
  ✓ mime_in[avatar,image/png,image/jpeg] - MIME type verified
```

**When It's Called:**
- POST request to `/users/store`
- User submits the registration form

**Return Values:**
- ✅ Success: Redirect to `/users` with success message
- ❌ Validation failed: Redirect back with error messages
- ❌ File invalid: Redirect back with file error

---

#### 5. `delete()` - Lines 146-169

**Purpose:** Remove user record and associated avatar file from disk.

```php
public function delete($id = null)
{
    // 1. Find user record
    $record = $this->userModel->find($id);
    
    if (!$record) {
        throw PageNotFoundException::forPageNotFound();
    }
    
    // 2. Delete avatar file from disk
    $filePath = FCPATH . 'uploads/' . $record['avatar'];
    if (file_exists($filePath) && $record['avatar'] !== 'default-avatar.png') {
        unlink($filePath);
    }
    
    // 3. Delete database record
    $this->userModel->delete($id);
    
    // 4. Clear entire cache
    cache()->clean();
    
    return redirect()->to('users')
                   ->with('success', 'State scrubbed and cache flushed.');
}
```

**Key Line Numbers:**

| Line | Function | Purpose |
|------|----------|---------|
| 148 | `find($id)` | Fetch user record |
| 150-151 | `PageNotFoundException` | Throw 404 if not found |
| 155 | `file_exists()` | Check if file on disk |
| 156 | Protection check | Prevent deletion of system files |
| 157 | `unlink()` | Delete file from disk |
| 161 | `delete()` | Remove from database |
| 166 | `cache()->clean()` | Flush all cached data |

**When It's Called:**
- GET request to `/users/delete/{id}`
- User clicks "Delete" button in list

**Why Cache is Cleared:**
- Pagination relies on cache keys like `users_list_md5(...)`
- If we don't clear cache, old pagination links might be stale
- Safer to clear entire cache (only 5-minute TTL anyway)

---

#### 6. `sendNotificationEmail()` - Lines 124-143

**Purpose:** Send welcome email to new user with dynamic content.

```php
private function sendNotificationEmail(array $userData)
{
    // 1. Get email service
    $email = \Config\Services::email();
    
    // 2. Set email recipient
    $email->setTo($userData['email']);
    
    // 3. Set email subject
    $email->setSubject('System Access Provisioned // Core Engine');
    
    // 4. Render email template with data
    $email->setMessage(view('email/welcome', [
        'name' => $userData['name'],
        'email' => $userData['email']
    ]));
    
    // 5. Send email
    if (!$email->send()) {
        log_message('warning', 'Notification failed: ' . $email->printDebugger(['headers']));
    }
}
```

**Key Features:**
- Private method (not accessible via URL)
- Receives user data array
- Uses dependency injection via `Services::email()`
- Renders PHP view as email body
- Logs failures as warnings (not critical)

**Configure Email:**
Edit `app/Config/Email.php`:
```php
public string $protocol = 'smtp'; // or 'sendmail'
public string $SMTPHost = 'smtp.gmail.com';
public int $SMTPPort = 587;
public string $SMTPUser = 'your-email@gmail.com';
public string $SMTPPass = 'app-specific-password';
```

---

## 📦 Model Functions

### File: `app/Models/UserModel.php`

#### Class Properties - Lines 9-16

```php
protected $table = 'users';                    // Database table name
protected $primaryKey = 'id';                  // Primary key column
protected $useAutoIncrement = true;            // ID auto-increments
protected $returnType = 'array';               // Results as PHP arrays
protected $useTimestamps = true;               // Manage timestamps
protected $allowedFields = ['name', 'email', 'avatar']; // Mass-assignment whitelist
```

**What Each Does:**

| Property | Value | Meaning |
|----------|-------|---------|
| `$table` | `users` | Maps to MySQL table `users` |
| `$primaryKey` | `id` | Column `id` is primary key |
| `$useAutoIncrement` | `true` | ID auto-increments on insert |
| `$returnType` | `array` | `find()` returns arrays not objects |
| `$useTimestamps` | `true` | Auto-manage `created_at`, `updated_at` |
| `$allowedFields` | array | Only these fields can be inserted/updated via `insert()` or `update()` |

**Why Important:**
- `$allowedFields` prevents mass-assignment attacks
- `$useTimestamps` means you don't manually set timestamps
- `$returnType = 'array'` makes looping easier in views

**Auto-Inherited Methods from CodeIgniter\Model:**

```php
// CRUD Operations (inherited, not defined in this file)
$userModel->find($id);           // Get single user by ID
$userModel->findAll();           // Get all users (NO pagination)
$userModel->insert($data);       // Create new record
$userModel->update($id, $data);  // Update record
$userModel->delete($id);         // Delete record

// Query Builder (inherited)
$userModel->like('name', 'john'); // WHERE name LIKE '%john%'
$userModel->where('id', 5);       // WHERE id = 5
$userModel->paginate(3);          // Limit results to 3 per page
$userModel->countAll();           // Count all records

// Property Access
$userModel->pager;  // Access the Pager instance (used in view)
```

---

## 🎨 View Functions

### File: `app/Views/users/create.php` - Upload Form

**Key HTML Elements:**

| Line | Element | Explanation |
|------|---------|-------------|
| 20 | `<form method="post" enctype="multipart/form-data">` | **CRITICAL:** enctype enables file upload |
| 21 | `<?= csrf_field() ?>` | Generates CSRF token for security |
| 23 | `<input type="text" name="name">` | Text input for name |
| 28 | `<input type="email" name="email">` | Email input (validates format) |
| 33 | `<input type="file" name="avatar">` | File input for avatar |
| 38 | `<button type="submit">` | Submit button |

**Form Processing:**
```
User fills form → Click Submit → POST to /users/store → UserController::store()
```

---

### File: `app/Views/users/index.php` - User Listing

**Key Sections:**

#### Search Form - Lines 9-16

```html
<form action="<?= site_url('users') ?>" method="get">
    <input type="text" name="search" value="<?= esc($searchKeyword) ?>">
    <button type="submit">Search</button>
</form>
```

**How It Works:**
1. User types in search box
2. Clicks "Search"
3. Form submits to `/users?search=keyword`
4. Controller receives `$_GET['search']` parameter
5. Filters results using `like()` query builder

**Why GET vs POST:**
- GET method keeps search term in URL
- Allows bookmarking: `http://localhost:8080/users?search=john`
- Better for filtering/navigation

#### User Table - Lines 20-50

```php
<table class="table">
    <thead>
        <tr>
            <th>Avatar</th>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <img src="<?= base_url('uploads/' . esc($user['avatar'])) ?>"
                         class="rounded-circle" style="width: 50px; height: 50px;">
                </td>
                <td><?= esc($user['name']) ?></td>
                <td><?= esc($user['email']) ?></td>
                <td>
                    <a href="<?= site_url('users/delete/' . $user['id']) ?>"
                       onclick="return confirm('Delete?')">
                        Delete
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

**Key Functions:**

| Function | Line | Purpose |
|----------|------|---------|
| `foreach ($users as $user)` | 31 | Loop through paginated results |
| `base_url()` | 34 | Convert to full URL path |
| `esc()` | 34, 36, 37 | Escape HTML to prevent XSS |
| `site_url()` | 39 | Generate CI4 route URL |
| `confirm()` | 40 | Show JS confirmation dialog |

#### Pagination Links - Lines 53-55

```php
<div class="mt-4 d-flex justify-content-center">
    <?= $pager_links ?>
</div>
```

**What `$pager_links` Contains:**

The string is rendered HTML from `line 53` in `UserController::index()`:
```php
'pager_links' => $this->userModel->pager->links('default', 'bootstrap_cms')
```

**Output Example:**
```html
<nav aria-label="Page navigation">
    <ul class="pagination">
        <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
        <li class="page-item active"><a class="page-link" href="?page=2">2</a></li>
        <li class="page-item"><a class="page-link" href="?page=3">3</a></li>
    </ul>
</nav>
```

---

### File: `app/Views/layouts/master.php` - Base Template

**Structure:**

```html
<!DOCTYPE html>
<html>
    <head>
        <!-- Meta tags -->
        <!-- Bootstrap CSS -->
    </head>
    <body>
        <nav><!-- Navigation bar --></nav>
        
        <main>
            <?= $this->renderSection('content') ?> <!-- DYNAMIC CONTENT GOES HERE -->
        </main>
        
        <footer></footer>
    </body>
</html>
```

**How Child Views Extend This:**

In `users/create.php`:
```php
<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>
    <!-- Form HTML here -->
<?= $this->endSection() ?>
```

**Result:**
- Master layout provides structure (nav, footer, CSS)
- Child view's content inserted into `renderSection()` placeholder
- All views share same look-and-feel

---

## 🛣️ Routing

### File: `app/Config/Routes.php`

**Route Definitions:**

```php
$routes->get('/', 'UserController::index');
$routes->get('users', 'UserController::index');
$routes->get('users/create', 'UserController::create');
$routes->post('users/store', 'UserController::store');
$routes->get('users/delete/(:num)', 'UserController::delete/$1');
```

**Route Parameters:**

| Route | Method | Controller::Method | Parameters |
|-------|--------|-------------------|------------|
| `/` | GET | `UserController::index` | None |
| `/users` | GET | `UserController::index` | None |
| `/users/create` | GET | `UserController::create` | None |
| `/users/store` | POST | `UserController::store` | None |
| `/users/delete/{id}` | GET | `UserController::delete` | `$id` = {id} |

**Route Matching Logic:**

```
Incoming Request: GET /users/delete/5
                  ↓
CodeIgniter Router examines all routes
                  ↓
Matches: $routes->get('users/delete/(:num)', ...)
                  ↓
Extracts parameter: 5
                  ↓
Calls: UserController::delete(5)
```

---

## 🔍 Helper Functions Used

| Function | From | Purpose |
|----------|------|---------|
| `view()` | CI4 Framework | Render view and return as string |
| `redirect()` | CI4 Framework | Create HTTP redirect response |
| `cache()` | CI4 Framework | Access cache service |
| `log_message()` | CI4 Framework | Write to logs |
| `base_url()` | CI4 Helper | Generate full URL base |
| `site_url()` | CI4 Helper | Generate full application URL |
| `esc()` | CI4 Helper | Escape HTML entities |
| `old()` | CI4 Helper | Retrieve old form input |
| `csrf_field()` | CI4 Helper | Generate CSRF token field |
| `$this->request->getGet()` | CI4 Request | Get URL query parameter |
| `$this->request->getPost()` | CI4 Request | Get POST body parameter |
| `$this->request->getFile()` | CI4 Request | Get uploaded file |
| `$this->validate()` | CI4 Request | Run validation rules |

---

## 🔄 Request/Response Cycle Example

### Example: User Registration

**Step 1: User visits form**
```
Browser: GET /users/create
Router matches: $routes->get('users/create', 'UserController::create')
Controller runs: return view('users/create')
Response: HTML form
```

**Step 2: User submits form**
```
Browser: POST /users/store (form data + file)
Router matches: $routes->post('users/store', 'UserController::store')

In store():
  1. Validate input → Line 82
  2. Get file → Line 87
  3. Generate random name → Line 90
  4. Move file to disk → Line 95
  5. Insert to DB → Line 104
  6. Send email → Line 107
  7. Redirect to /users

Response: HTTP 302 Redirect to /users
```

**Step 3: User sees listing**
```
Browser: GET /users
Router matches: $routes->get('users', 'UserController::index')

In index():
  1. Get search param → Line 24
  2. Check cache → Line 32
  3. Query database → Line 45
  4. Cache result → Line 57
  5. Return view → Line 64

Response: HTML table with users
```

---

**Last Updated:** June 1, 2026  
**Reference Version:** 1.0  
**Framework:** CodeIgniter 4
