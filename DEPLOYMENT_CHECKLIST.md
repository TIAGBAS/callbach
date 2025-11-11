# ✅ Railway Deployment Checklist

Use this checklist to ensure a smooth deployment:

## Pre-Deployment

- [ ] All code is committed to git
- [ ] `.gitignore` is properly configured
- [ ] Sensitive data (passwords, tokens) are not hardcoded (or you're okay with them in code)
- [ ] `rez/` directory exists with `.gitkeep` file

## GitHub Setup

- [ ] Created GitHub repository
- [ ] Pushed code to GitHub
- [ ] Verified all files are in the repository

## Railway Setup

- [ ] Created Railway account
- [ ] Connected GitHub account to Railway
- [ ] Created new project
- [ ] Selected your repository
- [ ] Deployment started successfully

## Post-Deployment

- [ ] Deployment completed (check Railway dashboard)
- [ ] Got your Railway URL (e.g., `your-app.railway.app`)
- [ ] Visited `create_db.php` once to initialize database
- [ ] Can access `check.php` panel
- [ ] Logged in with default password
- [ ] Changed default password
- [ ] Tested callback endpoint (`index.php`)
- [ ] Configured Telegram settings (if needed)

## Testing

- [ ] Panel loads correctly
- [ ] Can login
- [ ] Can view accounts (if any exist)
- [ ] Can create/update accounts via callback
- [ ] Database persists between page refreshes
- [ ] File logging works (if enabled)
- [ ] Telegram integration works (if configured)

## Security

- [ ] Changed default password
- [ ] HTTPS is working (Railway provides automatically)
- [ ] Sensitive credentials are secure
- [ ] Database file is not in git

## Optional

- [ ] Set up custom domain
- [ ] Configured environment variables
- [ ] Set up monitoring/alerts
- [ ] Reviewed Railway usage/limits

---

**Deployment Complete! 🎉**

If all items are checked, your KVIL Panel is successfully deployed and ready to use!

