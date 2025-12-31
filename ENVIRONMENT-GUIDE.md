# 🔧 Database Environment Switching Guide

## Current Setup

Your `api.php` now supports **3 environments**:
1. **Local** (localhost/XAMPP)
2. **InfinityFree** (current default)
3. **Railway** (production)

---

## 🚀 How to Switch Environments

### Method 1: Change Default in Code (Quick & Easy)

Open `api.php` and find line ~35:

```php
$environment = getenv('DB_ENV') ?: 'infinityfree'; // Default: infinityfree
```

**Change to:**

**For Local Development:**
```php
$environment = getenv('DB_ENV') ?: 'local';
```

**For InfinityFree:**
```php
$environment = getenv('DB_ENV') ?: 'infinityfree';
```

**For Railway:**
```php
$environment = getenv('DB_ENV') ?: 'railway';
```

### Method 2: Environment Variable (Professional)

Set `DB_ENV` environment variable:

**Windows (Command Prompt):**
```cmd
set DB_ENV=railway
```

**Linux/Mac:**
```bash
export DB_ENV=railway
```

**Railway Dashboard:**
Add variable: `DB_ENV` = `railway`

---

## 📋 Current Configurations

### 🏠 Local (localhost)
```
Host: localhost
User: root
Pass: (empty)
Database: ultrabase
Port: 3306
```

### 🌐 InfinityFree (DEFAULT)
```
Host: sql110.infinityfree.com
User: if0_40793018
Pass: z1d5FL69C8Eh
Database: if0_40793018_ultrabase
Port: 3306
```

### 🚂 Railway (Production)
```
Host: tram4ay.proxy.rlwy.net
User: root
Pass: dGIXnHczcIeccCrrLjImfaiVjEaLRM1p
Database: railway
Port: 43439
```

---

## ✅ Quick Test

After switching, test with:
```
http://your-url/api.php?action=get_projects&key=maxclube_secret
```

Should return your projects list!

---

## 🎯 Recommended Workflow

1. **Development:** Use `local`
2. **Testing:** Use `infinityfree`
3. **Production:** Use `railway`

---

## 💡 Pro Tips

- Keep `infinityfree` as default for easy testing
- Use Railway for production (automatic HTTPS!)
- Local for offline development
- No need to change code when deploying - just set environment variable!

---

## 🔄 One-Line Switch

Just change this line in `api.php`:
```php
$environment = getenv('DB_ENV') ?: 'YOUR_CHOICE_HERE';
```

Replace `YOUR_CHOICE_HERE` with: `local`, `infinityfree`, or `railway`

Done! 🎉
