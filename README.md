# KVIL Panel

A sophisticated account management dashboard with dark theme UI, bulk operations, and Telegram integration.

## Features

- 🔐 Secure authentication system
- 📊 Dashboard with account management
- 🎨 Modern dark theme UI
- ✅ Bulk account selection and deletion
- 📱 Telegram integration
- 🍪 Cookie management with StorageAce format
- 🔄 Duplicate prevention
- 📧 Email notifications

## Railway Deployment

### Quick Deploy

1. **Push to GitHub:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin YOUR_GITHUB_REPO_URL
   git push -u origin main
   ```

2. **Deploy on Railway:**
   - Go to [railway.app](https://railway.app)
   - Sign up/Login with GitHub
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your repository
   - Railway will auto-detect PHP and deploy

3. **Access Your Panel:**
   - Railway will provide a URL like: `your-app.railway.app`
   - Access panel: `https://your-app.railway.app/check.php`
   - Callback endpoint: `https://your-app.railway.app/index.php`
   - Initialize database: `https://your-app.railway.app/create_db.php` (run once)

### Initial Setup

1. **Initialize Database:**
   - Visit `https://your-app.railway.app/create_db.php` once
   - This creates the database structure

2. **Access Panel:**
   - Go to `https://your-app.railway.app/check.php`
   - Default password: `HitTheGroundRunning.exe`
   - Change password after first login

3. **Configure Telegram (Optional):**
   - Go to Settings in the panel
   - Enter your Telegram bot token and chat ID
   - Save settings

## File Structure

```
callback/
├── index.php          # Callback endpoint (receives data)
├── check.php          # Main dashboard panel
├── create_db.php      # Database initialization
├── Procfile           # Railway process file
├── composer.json      # PHP dependencies
├── railway.json       # Railway configuration
├── nixpacks.toml      # Nixpacks build config
├── .gitignore         # Git ignore rules
└── rez/              # Log files directory (auto-created)
```

## Environment Variables (Optional)

Railway allows you to set environment variables if needed:
- `PHP_VERSION` - PHP version (default: auto-detect)
- `PORT` - Server port (auto-set by Railway)

## Requirements

- PHP 7.4 or higher
- PDO SQLite extension
- cURL extension
- Session support

## Security Notes

- Change default password immediately
- Use HTTPS (Railway provides automatically)
- Keep your Telegram bot token secure
- Database file is excluded from git

## Support

For issues or questions, check the deployment logs in Railway dashboard.

