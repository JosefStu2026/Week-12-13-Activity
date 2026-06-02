# Setup & Installation Guide

Complete step-by-step instructions to get the User Profile System running locally.

## 📋 Prerequisites Checklist

Before starting, ensure you have:

- [ ] PHP 8.2 or higher installed
- [ ] MySQL 5.7+ or MariaDB installed
- [ ] Composer installed globally
- [ ] A code editor (VS Code, PHPStorm, etc.)
- [ ] Command line/terminal access
- [ ] Git (optional, for version control)

### Verify Your Environment

**Check PHP Version:**
```bash
php -v
# Should output PHP 8.2.0 or higher
```

**Check MySQL:**
```bash
mysql --version
# Should output MySQL 5.7+ or MariaDB
```

**Check Composer:**
```bash
composer --version
# Should output Composer 2.x
```

---

## 🚀 Installation Steps

### Step 1: Navigate to Project Directory

```bash
cd Week_12_13_fupl_system
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

**What happens:**
- Downloads CodeIgniter 4 framework
- Installs all required PHP libraries
- Creates `vendor/` directory
- Generates autoloader configuration

**Expected Output:**
```
Installing dependencies from lock file
Loading composer repositories
Installing packages (50+ packages)
...
✓ Installation complete
```

### Step 3: Configure Environment Variables

**Copy the environment template:**
```bash
cp env .env
```

**Edit `.env` file** (open in your editor):

```env
# Application Settings
app.baseURL = 'http://localhost:8080/'
app.environment = development

# Database Configuration
database.default.hostname = localhost
database.default.username = root
database.default.password = 
database.default.database = fupl_system
database.default.DBDriver = MySQLi
database.default.DBPrefix = 
database.default.port = 3306
database.default.charset = utf8mb4

# Email Configuration (Optional)
email.fromEmail = 'noreply@engineershq.local'
email.fromName = 'EngineersHQ CRUD System'
email.protocol = 'smtp'
email.SMTPHost = 'smtp.gmail.com'
email.SMTPUser = 'your-email@gmail.com'
email.SMTPPass = 'your-app-password'
email.SMTPPort = 587
```

**Key Settings to Update:**
- `database.default.username` - Your MySQL root user or dedicated user
- `database.default.password` - Your MySQL password
- `database.default.database` - Keep as `fupl_system`
- `app.baseURL` - Change if not using localhost:8080

### Step 4: Create MySQL Database

**Option A: Using MySQL Command Line**

```bash
mysql -u root -p
```

Then in MySQL prompt:
```sql
-- Create database
CREATE DATABASE fupl_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create users table
USE fupl_system;

CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify table creation
SHOW TABLES;
DESC users;

-- Exit MySQL
EXIT;
```

**Option B: Using PHPMyAdmin**
1. Open PHPMyAdmin (usually http://localhost/phpmyadmin)
2. Click "New" → Enter database name: `fupl_system`
3. Select charset: `utf8mb4_unicode_ci`
4. Click "Create"
5. In the SQL tab, paste the CREATE TABLE statement above
6. Click Execute

**Option C: Using a GUI Tool**
- **MySQL Workbench:** File → Create new schema → name it `fupl_system` → apply
- **TablePlus:** New → MySQL → Connect → right-click → Create DB → name `fupl_system`

### Step 5: Create File Upload Directory

**Create the uploads directory:**

**On Windows (PowerShell):**
```powershell
mkdir public\uploads
```

**On Mac/Linux (Terminal):**
```bash
mkdir -p public/uploads
chmod 755 public/uploads
```

**On Windows (Command Prompt):**
```cmd
mkdir public\uploads
```

**Verify the directory exists:**
```bash
ls public/uploads/
# or on Windows: dir public\uploads
```

### Step 6: Create Logs Directory

The `writable/logs/` directory should already exist. If not:

```bash
mkdir -p writable/logs
chmod 755 writable/logs
```

### Step 7: Test Database Connection

**Create a test file** `test_db.php` in the root directory:

```php
<?php
require 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Test database connection
$db = \Config\Database::connect();
$builder = $db->table('users');
$count = $builder->countAll();

echo "✓ Database connection successful!\n";
echo "Users table has $count records.\n";
?>
```

**Run the test:**
```bash
php test_db.php
```

**Expected Output:**
```
✓ Database connection successful!
Users table has 0 records.
```

**Delete the test file:**
```bash
rm test_db.php
```

---

## 🌐 Start Development Server

### Using PHP Built-in Server (Recommended for Development)

```bash
php spark serve
```

**Expected Output:**
```
CodeIgniter v4.x.x Command Line Tool
Server started on http://localhost:8080
Press Control + C to stop
```

**Access the application:**
- Open browser → `http://localhost:8080`
- You should see the user listing page

### Using Apache/Nginx (Production)

**For Apache:**
1. Point DocumentRoot to `public/` directory
2. Enable `mod_rewrite` and `.htaccess`
3. Configure virtual host

**For Nginx:**
1. Point root to `public/` directory
2. Configure rewrite rules for CI4
3. Ensure PHP-FPM is running

---

## ✅ Verification Steps

After setup, verify everything works:

### 1. Homepage Load
```
Visit: http://localhost:8080
Expected: User listing page with heading "User Base Directory"
```

### 2. Create User Page
```
Click: "Register New User" button
Expected: Form with Name, Email, and File upload fields
```

### 3. Upload Form Submission
```
Fill form:
- Name: John Doe
- Email: john@example.com
- Avatar: Select any PNG/JPG file

Click: Save
Expected: Redirected to user list with success message
         Avatar appears in the table
```

### 4. Search Functionality
```
Type in search box: "John"
Click: Search
Expected: Only John's record appears in table
```

### 5. Pagination
```
If you have >3 users, pagination should appear
Expected: Page numbers clickable at bottom
         Links navigate between pages
```

### 6. Delete User
```
Click: Delete button on any user
Expected: Confirmation dialog appears
         On confirm: User removed + success message
                    Avatar file deleted from public/uploads/
```

### 7. Database Check
```
In MySQL:
USE fupl_system;
SELECT * FROM users;

Expected: All created users visible in table
          Timestamps in created_at and updated_at columns
```

### 8. File System Check
```
Open: public/uploads/ directory
Expected: Avatar files stored with randomized names
         (e.g., 1234567890abcdef.jpg)
```

---

## 🔍 Troubleshooting

### Issue: "Connection refused" (Database)

**Causes:**
- MySQL not running
- Wrong hostname/port in `.env`
- Wrong username/password

**Solutions:**
```bash
# Check MySQL status on Windows
Get-Service MySQL

# Start MySQL on Windows
Start-Service MySQL

# On Mac/Linux
brew services start mysql
# or
sudo systemctl start mysql

# Verify MySQL port is open
netstat -an | grep 3306
```

### Issue: "Directory not writable" Error

**Cause:** `public/uploads/` directory doesn't exist or has wrong permissions

**Solution:**
```bash
# Windows
mkdir public\uploads

# Mac/Linux
mkdir -p public/uploads
chmod 777 public/uploads
```

### Issue: "File not found" - 404 Error

**Cause:** Routes not configured or mod_rewrite not enabled

**Solution:**
```bash
# Check Routes.php exists
ls app/Config/Routes.php

# If using Apache, enable mod_rewrite
a2enmod rewrite
systemctl restart apache2
```

### Issue: Email Not Sending

**Cause:** SMTP settings not configured

**Solution:**
1. Check `.env` email settings
2. Verify SMTP credentials are correct
3. Allow "Less Secure App Access" if using Gmail
4. Check `writable/logs/` for error details

### Issue: File Upload Rejected

**Cause:** File type validation failure

**Solutions:**
```
✓ Ensure file is PNG or JPG
✓ Ensure file size < 2MB
✓ Check MIME type with file command:
  file -b --mime-type avatar.jpg
  # Should output: image/jpeg
```

### Issue: Blank Page on Load

**Cause:** Database connection failed or syntax error

**Solution:**
```bash
# Check .env database credentials
cat .env | grep database

# Check PHP syntax
php -l app/Controllers/UserController.php

# Check logs
tail -f writable/logs/log-*.log
```

---

## 📊 Database Import/Export

### Export Database (Backup)

**Using MySQL Command:**
```bash
mysqldump -u root -p fupl_system > fupl_backup.sql
```

**Using PHPMyAdmin:**
1. Select `fupl_system` database
2. Click "Export" tab
3. Click "Go" to download SQL file

### Import Database (Restore)

**Using MySQL Command:**
```bash
mysql -u root -p fupl_system < fupl_backup.sql
```

---

## 🔐 Security Checklist

Before going to production:

- [ ] Change `CI_ENVIRONMENT` from `development` to `production` in `.env`
- [ ] Set `CI_DEBUG` to `0` in `.env`
- [ ] Update database password in `.env`
- [ ] Configure SMTP credentials for email
- [ ] Set strong encryption key in `.env`
- [ ] Disable directory listing (add .htaccess to public/)
- [ ] Use HTTPS instead of HTTP
- [ ] Set file permissions correctly:
  - `public/uploads/` - 755
  - `writable/` - 755
  - `app/`, `vendor/` - 755

---

## 📦 Next Steps

After successful installation:

1. **Explore the Code:**
   - Read through `app/Controllers/UserController.php`
   - Review `app/Models/UserModel.php`
   - Check `app/Views/` for templates

2. **Run Tests:**
   - Complete all items in verification steps above
   - Try edge cases (large files, invalid emails, etc.)

3. **Customize:**
   - Change pagination limit (line 45 in UserController)
   - Modify file size limit (line 79 in UserController)
   - Update allowed file types (line 79)
   - Change cache duration (line 57)

4. **Deploy (Optional):**
   - Push to GitHub
   - Deploy to hosting provider
   - Set up continuous integration

---

## 📞 Getting Help

If you encounter issues:

1. **Check Logs:** `tail -f writable/logs/log-*.log`
2. **Read Main Documentation:** [DOCUMENTATION.md](./DOCUMENTATION.md)
3. **Review Code Comments:** Check inline comments in Controller
4. **CodeIgniter Docs:** https://codeigniter.com/user_guide/
5. **Database Tools:** Use PHPMyAdmin or MySQL Workbench to debug

---

**Setup Completed Successfully!** 🎉

Your User Profile System is now ready for development and testing.

---

**Last Updated:** June 1, 2026  
**Guide Version:** 1.0  
**Framework:** CodeIgniter 4
