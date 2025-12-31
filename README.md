# 🚀 UltraBase - Complete Backend Solution

**UltraBase** ek powerful, Firebase-like backend service hai jo developers ko bina complex server setup ke complete database solution provide karta hai.

## ✨ Features

### 🔥 Core Features
- **Real-time NoSQL Database** - JSON-based data storage
- **User Authentication** - Login/Register system with sessions
- **Multi-user Support** - Har user ke alag projects
- **Collapsible Tree View** - Nested data ko easily visualize karein
- **API Key Management** - Secure API keys with permissions
- **Activity Logs** - Complete audit trail
- **Backup & Restore** - One-click data backup
- **Webhooks** - Event-triggered notifications
- **Global Search** - Search across all collections
- **Analytics** - Track reads, writes & activity

### 📱 Perfect For
- Android Apps
- Web Applications
- Mobile Games
- IoT Projects
- Prototyping
- Learning Projects

## 🎯 Quick Start

### 1. Setup
```bash
# Database setup
# Import to MySQL or let auto-setup create tables

# Update api.php with your database credentials
$host = "localhost";
$user = "root";
$pass = "";
$db = "ultrabase";
```

### 2. Register
```
http://localhost/htdocs/register.html
```

### 3. Create Project
```
http://localhost/htdocs/index.html
```

### 4. Start Using!
```
http://localhost/htdocs/console.html?pid=1
```

## 📚 Documentation

Complete documentation available at:
```
http://localhost/htdocs/docs.html
```

### Documentation Includes:
- ✅ Android Integration Guide (Kotlin)
- ✅ Complete API Reference
- ✅ Code Examples
- ✅ Best Practices
- ✅ Security Guidelines

## 🔌 API Endpoints

### Authentication
- `register` - Create new account
- `login` - User login
- `logout` - User logout
- `check_session` - Verify session

### Data Management
- `save` - Save/update data
- `get` - Retrieve data
- `search_data` - Search across collections

### Projects
- `get_projects` - List user's projects
- `create_project` - Create new project
- `delete_project` - Delete project

### Advanced
- `create_backup` - Create backup
- `restore_backup` - Restore from backup
- `add_webhook` - Add webhook
- `get_activity_logs` - Get activity logs
- `generate_api_key` - Generate API key

## 📱 Android Integration

### Dependencies
```gradle
dependencies {
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3'
}
```

### Basic Usage
```kotlin
// Setup
object RetrofitClient {
    private const val BASE_URL = "http://your-domain.com/htdocs/"
    const val API_KEY = "maxclube_secret"
    const val PROJECT_ID = 1
}

// Save Data
suspend fun saveData(collection: String, data: Any) {
    val request = SaveRequest(
        key = API_KEY,
        pid = PROJECT_ID,
        collection = collection,
        data = data
    )
    api.saveData(request)
}

// Get Data
suspend fun getData(collection: String) {
    val response = api.getData(
        key = API_KEY,
        projectId = PROJECT_ID,
        collection = collection
    )
}
```

## 🗂️ Project Structure

```
htdocs/
├── api.php              # Backend API
├── index.html           # Dashboard
├── console.html         # Project Console
├── login.html           # Login Page
├── register.html        # Register Page
├── docs.html            # Documentation
└── README.md            # This file
```

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ Session management
- ✅ API key authentication
- ✅ SQL injection protection (prepared statements)
- ✅ User-specific data isolation
- ✅ Activity logging

## 🎨 UI Features

- ✅ Modern, responsive design
- ✅ Dark/Light theme toggle
- ✅ Collapsible tree view for nested data
- ✅ Real-time status indicators
- ✅ Toast notifications
- ✅ Smooth animations
- ✅ Material Icons

## 📊 Database Schema

### Tables
- `users` - User accounts
- `projects` - User projects
- `universal_data` - JSON data storage
- `analytics` - Usage statistics
- `api_keys` - API key management
- `activity_logs` - Audit trail
- `backups` - Data backups
- `webhooks` - Webhook configurations
- `security_rules` - Access control rules

## 🛠️ Tech Stack

### Backend
- PHP 7.4+
- MySQL 5.7+
- Session-based authentication
- RESTful API

### Frontend
- HTML5
- CSS3 (Custom properties, Grid, Flexbox)
- Vanilla JavaScript (ES6+)
- Material Icons
- Inter & JetBrains Mono fonts

## 📖 Usage Examples

### Chat App
```kotlin
data class Message(
    val sender: String,
    val text: String,
    val timestamp: Long
)

fun sendMessage(sender: String, text: String) {
    val message = Message(sender, text, System.currentTimeMillis())
    repository.saveData("messages", message)
}
```

### User Profile
```kotlin
fun saveProfile(name: String, bio: String) {
    val profile = mapOf(
        "name" to name,
        "bio" to bio,
        "updated_at" to System.currentTimeMillis()
    )
    repository.saveData("profile", profile)
}
```

### Leaderboard
```kotlin
fun saveScore(player: String, score: Int) {
    val scoreData = mapOf(
        "player" to player,
        "score" to score,
        "date" to System.currentTimeMillis()
    )
    repository.saveData("leaderboard", scoreData)
}
```

## 🚦 Getting Started Checklist

- [ ] Setup database
- [ ] Configure api.php credentials
- [ ] Register account
- [ ] Create first project
- [ ] Read documentation
- [ ] Integrate in Android app
- [ ] Test API endpoints
- [ ] Deploy to production

## 🌟 Key Advantages

1. **No Complex Setup** - Just PHP + MySQL
2. **Firebase-like Experience** - Familiar API structure
3. **Complete Control** - Self-hosted solution
4. **Free & Open** - No usage limits
5. **Easy Integration** - Simple REST API
6. **Multi-platform** - Works with any language
7. **Professional Features** - Backups, webhooks, logs
8. **Beautiful UI** - Modern, responsive interface

## 📞 Support

For detailed documentation and examples:
- Visit: `http://localhost/htdocs/docs.html`
- Check: `walkthrough.md` for implementation details

## 🎉 Credits

Built with ❤️ for developers who need a simple, powerful backend solution.

---

**UltraBase** - Your Complete Backend Solution 🚀
