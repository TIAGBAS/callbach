# Deployment Guide for KVIL Panel

## ⚠️ Vercel Limitations

**Vercel is NOT recommended for this application** due to the following issues:

### Problems with Vercel:
1. **SQLite Database**: Vercel's serverless functions are stateless. SQLite files won't persist between function invocations.
2. **File System**: Vercel's file system is read-only except for `/tmp` (temporary). Your `rez/` directory and database files won't persist.
3. **Sessions**: File-based sessions won't work properly without persistent storage.
4. **Serverless Cold Starts**: Each request might start a new function instance, losing in-memory data.

## ✅ Better Alternatives

### 1. **Railway** (Recommended)
- ✅ Full PHP support
- ✅ Persistent file storage
- ✅ SQLite works perfectly
- ✅ Free tier available
- ✅ Easy deployment from GitHub

**Deployment Steps:**
1. Push your code to GitHub
2. Go to [railway.app](https://railway.app)
3. Create new project → Deploy from GitHub
4. Select your repository
5. Railway auto-detects PHP and deploys

### 2. **Render**
- ✅ PHP support
- ✅ Persistent storage
- ✅ Free tier available
- ✅ Easy setup

**Deployment Steps:**
1. Push code to GitHub
2. Go to [render.com](https://render.com)
3. New → Web Service
4. Connect GitHub repo
5. Build command: (auto-detected)
6. Start command: `php -S 0.0.0.0:$PORT`

### 3. **Heroku**
- ✅ PHP support
- ✅ Add-ons for databases
- ⚠️ No free tier anymore (paid)

### 4. **DigitalOcean App Platform**
- ✅ PHP support
- ✅ Persistent storage
- ⚠️ Paid service

### 5. **Traditional VPS (cPanel, etc.)**
- ✅ Full control
- ✅ Everything works as-is
- ✅ Your current setup

## 🔧 If You Still Want Vercel

To make it work on Vercel, you would need to:

1. **Replace SQLite with external database:**
   - Use PostgreSQL (Vercel Postgres)
   - Or MySQL (PlanetScale, Supabase)
   - Update all database queries

2. **Use external storage for files:**
   - AWS S3
   - Cloudinary
   - Or disable file logging

3. **Use external session storage:**
   - Redis (Upstash)
   - Database sessions

4. **Create `vercel.json` configuration:**

```json
{
  "version": 2,
  "builds": [
    {
      "src": "index.php",
      "use": "@vercel/php"
    },
    {
      "src": "check.php",
      "use": "@vercel/php"
    },
    {
      "src": "create_db.php",
      "use": "@vercel/php"
    }
  ],
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/$1"
    }
  ]
}
```

## 📋 Recommended: Railway Deployment

### Step-by-Step Railway Setup:

1. **Prepare your repository:**
   ```bash
   # Create .gitignore if needed
   echo "passwords.db" >> .gitignore
   echo "rez/*.txt" >> .gitignore
   ```

2. **Create `Procfile` (optional, Railway auto-detects PHP):**
   ```
   web: php -S 0.0.0.0:$PORT
   ```

3. **Push to GitHub:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin YOUR_GITHUB_REPO
   git push -u origin main
   ```

4. **Deploy on Railway:**
   - Sign up at railway.app
   - New Project → Deploy from GitHub
   - Select your repo
   - Railway will auto-detect PHP
   - Your app will be live!

5. **Access your panel:**
   - Railway provides a URL like: `your-app.railway.app`
   - Access `your-app.railway.app/check.php` for the panel
   - Access `your-app.railway.app/index.php` for the callback

## 🔐 Environment Variables

For Railway/Render, you can set environment variables:
- Database path (if using external DB)
- Email settings
- Telegram bot token (optional)

## 📝 Notes

- **Database**: SQLite will work on Railway/Render with persistent storage
- **File Logging**: The `rez/` directory will persist on Railway/Render
- **Sessions**: Will work normally on these platforms
- **Email**: `mail()` function may not work - consider using SMTP library
- **HTTPS**: All platforms provide HTTPS automatically

## 🚀 Quick Start with Railway

1. Install Railway CLI (optional):
   ```bash
   npm i -g @railway/cli
   ```

2. Login:
   ```bash
   railway login
   ```

3. Initialize:
   ```bash
   railway init
   ```

4. Deploy:
   ```bash
   railway up
   ```

Your app will be live in minutes!

---

**Recommendation**: Use **Railway** or **Render** for the easiest deployment with full PHP support and persistent storage.

