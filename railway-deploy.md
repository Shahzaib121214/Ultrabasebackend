# 🚂 Railway Deployment Guide - UltraBase

## Railway MySQL Connection Details

Based on your screenshots:

**Connection URL:**
```
mysql://root:dGIXnHczcIeccCrrLjImfaiVjEaLRM1p@tram4ay.proxy.rlwy.net:43439/railway
```

**Credentials:**
- **Host:** `tram4ay.proxy.rlwy.net`
- **Port:** `43439`
- **Username:** `root`
- **Password:** `dGIXnHczcIeccCrrLjImfaiVjEaLRM1p`
- **Database:** `railway`

---

## 🔧 Step 1: Update `api.php` Configuration

Replace the database connection section in `api.php`:

```php
<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Railway MySQL Configuration
$host = "tram4ay.proxy.rlwy.net";
$port = "43439";
$user = "root";
$pass = "dGIXnHczcIeccCrrLjImfaiVjEaLRM1p";
$db = "railway";

// Create connection with port
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");

// Rest of your code...
```

---

## 📦 Step 2: Deploy to Railway

### Option A: GitHub Deployment (Recommended)

1. **Create GitHub Repository**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git branch -M main
   git remote add origin YOUR_GITHUB_REPO_URL
   git push -u origin main
   ```

2. **Connect to Railway**
   - Go to [railway.app](https://railway.app)
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your repository
   - Railway will auto-deploy!

### Option B: Railway CLI Deployment

1. **Install Railway CLI**
   ```bash
   npm install -g @railway/cli
   ```

2. **Login**
   ```bash
   railway login
   ```

3. **Initialize Project**
   ```bash
   railway init
   ```

4. **Link to MySQL Service**
   ```bash
   railway link
   ```

5. **Deploy**
   ```bash
   railway up
   ```

---

## 🌐 Step 3: Configure Environment Variables (Optional)

Instead of hardcoding credentials, use environment variables:

### In Railway Dashboard:
1. Go to your project
2. Click on "Variables" tab
3. Add these variables:
   - `DB_HOST` = `tram4ay.proxy.rlwy.net`
   - `DB_PORT` = `43439`
   - `DB_USER` = `root`
   - `DB_PASS` = `dGIXnHczcIeccCrrLjImfaiVjEaLRM1p`
   - `DB_NAME` = `railway`

### Update `api.php`:
```php
// Use environment variables
$host = getenv('DB_HOST') ?: 'tram4ay.proxy.rlwy.net';
$port = getenv('DB_PORT') ?: '43439';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'dGIXnHczcIeccCrrLjImfaiVjEaLRM1p';
$db = getenv('DB_NAME') ?: 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);
```

---

## 📝 Step 4: Create `railway.json` (Optional)

Create a `railway.json` file in your project root:

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

---

## 🔒 Step 5: HTTPS Configuration

Railway automatically provides HTTPS! Your URL will be:
```
https://your-project-name.up.railway.app
```

Update your Android/Web integration URLs to use this domain.

---

## ✅ Step 6: Test Connection

### Test Script (`test-db.php`):
```php
<?php
$host = "tram4ay.proxy.rlwy.net";
$port = "43439";
$user = "root";
$pass = "dGIXnHczcIeccCrrLjImfaiVjEaLRM1p";
$db = "railway";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected successfully to Railway MySQL!<br>";
echo "Server: " . $conn->host_info . "<br>";
echo "Database: " . $db;

$conn->close();
?>
```

---

## 🚀 Step 7: Update Frontend URLs

### In `android.html` and `web.html`:

Update the base URL to your Railway deployment:

```javascript
const currentUrl = "https://your-project-name.up.railway.app/";
```

### In Android App:
```kotlin
object RetrofitClient {
    private const val BASE_URL = "https://your-project-name.up.railway.app/"
    const val API_KEY = "maxclube_secret"
    const val PROJECT_ID = 1
}
```

---

## 📊 Railway Dashboard Features

### Monitor Your App:
1. **Deployments** - View deployment history
2. **Metrics** - CPU, Memory, Network usage
3. **Logs** - Real-time application logs
4. **Variables** - Environment variables
5. **Settings** - Domain, scaling, etc.

---

## 🔧 Common Issues & Solutions

### Issue 1: Connection Timeout
**Solution:** Check if Railway MySQL service is running
```bash
railway status
```

### Issue 2: Tables Not Created
**Solution:** Run the auto-setup by accessing:
```
https://your-project-name.up.railway.app/api.php?action=get_projects&key=maxclube_secret
```

### Issue 3: CORS Errors
**Solution:** Already handled in `api.php` with:
```php
header("Access-Control-Allow-Origin: *");
```

---

## 💰 Pricing

Railway offers:
- **Free Tier:** $5 credit per month
- **Pro Plan:** $20/month with $20 credit
- **Pay as you go** after credits

Your MySQL database will consume credits based on:
- CPU usage
- Memory usage
- Network egress

---

## 🎯 Quick Checklist

- [ ] Update `api.php` with Railway MySQL credentials
- [ ] Test local connection with Railway database
- [ ] Push code to GitHub
- [ ] Deploy to Railway
- [ ] Test API endpoints
- [ ] Update Android/Web URLs
- [ ] Monitor deployment logs
- [ ] Set up custom domain (optional)

---

## 📱 Final URLs

After deployment, your URLs will be:

- **API:** `https://your-project-name.up.railway.app/api.php`
- **Dashboard:** `https://your-project-name.up.railway.app/index.html`
- **Console:** `https://your-project-name.up.railway.app/console.html`
- **Docs:** `https://your-project-name.up.railway.app/docs.html`
- **Android Guide:** `https://your-project-name.up.railway.app/android.html`
- **Web Guide:** `https://your-project-name.up.railway.app/web.html`

---

## 🎉 Success!

Once deployed:
1. ✅ Automatic HTTPS
2. ✅ No "Dangerous Site" warnings
3. ✅ Production-ready database
4. ✅ Global CDN
5. ✅ Auto-scaling
6. ✅ Zero downtime deployments

Your UltraBase is now live and ready for production! 🚀
