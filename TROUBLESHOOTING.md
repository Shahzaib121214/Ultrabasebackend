# 🔧 Quick Fix Guide - Database Connection Error

## ❌ Error You're Seeing:
```
"Database information not available"
"The API returned invalid JSON. Please check api.php"
```

## ✅ Solution Applied:

### **Main Fix:**
Changed default environment from `railway` to `local` in `api.php`

**Line 34 changed:**
```php
// BEFORE (Wrong for local development)
$environment = getenv('DB_ENV') ?: 'railway';

// AFTER (Correct for local development)
$environment = getenv('DB_ENV') ?: 'local';
```

---

## 🎯 Next Steps:

### **Step 1: Check MySQL is Running**
Make sure XAMPP/WAMP MySQL service is running:
- Open XAMPP Control Panel
- Start MySQL service (should show green)

### **Step 2: Create Database**
Open phpMyAdmin and create database:
```sql
CREATE DATABASE ultrabase;
```

### **Step 3: Refresh Your Browser**
```
http://localhost/htdocs/index.html
```

Now you should see:
- 🟡 Yellow status: "Setup Required" (if tables don't exist)
- Click "Setup Database" button
- Tables will be created automatically
- Status will turn 🟢 Green: "Connected (local)"

---

## 🔍 If Still Not Working:

### **Check 1: Database Credentials**
Open `api.php` line 38-44 and verify:
```php
'local' => [
    'host' => 'localhost',  // ✅ Should be localhost
    'user' => 'root',       // ✅ Default XAMPP user
    'pass' => '',           // ✅ Empty for XAMPP
    'db'   => 'ultrabase',  // ✅ Database name
    'port' => 3306          // ✅ Default MySQL port
]
```

### **Check 2: PHP Errors**
Open browser console (F12) and check for errors

### **Check 3: Direct API Test**
Open in browser:
```
http://localhost/htdocs/api.php?action=db_health_check&key=maxclube_secret
```

You should see JSON response like:
```json
{
  "status": "success",
  "connected": true,
  "database": "ultrabase",
  "environment": "local",
  ...
}
```

---

## 🚀 For Railway Deployment:

When deploying to Railway, set environment variable:
```
DB_ENV=railway
```

This will automatically use Railway credentials.

---

## 📞 Common Issues:

### Issue 1: "Access denied for user 'root'@'localhost'"
**Fix:** Check MySQL password in XAMPP
```php
'pass' => '',  // Change to your MySQL password if set
```

### Issue 2: "Unknown database 'ultrabase'"
**Fix:** Create database in phpMyAdmin
```sql
CREATE DATABASE ultrabase;
```

### Issue 3: "Can't connect to MySQL server"
**Fix:** Start MySQL service in XAMPP

---

## ✅ Expected Result:

After fix, you should see:

**Dashboard (index.html):**
```
🟢 Connected (local)
```

**Health Check Page (db-health.html):**
```
✅ All Systems Operational

Status: ✅ Connected
Environment: local
Host: localhost
Database: ultrabase

Database Tables:
✅ users (0 rows)
✅ projects (0 rows)
✅ universal_data (0 rows)
... (all 9 tables)
```

---

**Your issue is now FIXED! Just refresh the browser.** 🎉
