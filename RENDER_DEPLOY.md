# Deploy Pet Tracking App to Render (Free Plan)

## 🚀 Quick Deployment Guide

### Prerequisites
- GitHub account
- Render account (free at render.com)
- Your code pushed to a GitHub repository

### Step 1: Push Code to GitHub
1. **Initialize Git repository** (if not already done):
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```

2. **Create GitHub repository**:
   - Go to github.com and create a new repository
   - Name it something like "pet-tracking-app"

3. **Push to GitHub**:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/pet-tracking-app.git
   git branch -M main
   git push -u origin main
   ```

### Step 2: Create Render Account
1. Go to [render.com](https://render.com)
2. Sign up with GitHub (recommended)
3. Authorize Render to access your repositories

### Step 3: Deploy Database First
1. **In Render Dashboard**, click "New +"
2. Select "PostgreSQL" (Free plan includes PostgreSQL, not MySQL)
3. Configure:
   - **Name**: `pet-tracking-db`
   - **Database**: `pawsitive_patrol`
   - **User**: `pawsitive_patrol_user`
   - **Region**: Choose closest to your users
4. Click "Create Database"
5. **Wait for database to be ready** (2-3 minutes)

### Step 4: Deploy Web Service
1. **In Render Dashboard**, click "New +"
2. Select "Web Service"
3. Connect your GitHub repository
4. Configure:
   - **Name**: `pet-tracking-app`
   - **Root Directory**: Leave empty (if code is in root)
   - **Environment**: `PHP`
   - **Build Command**: 
     ```bash
     mkdir -p uploads qr_codes && chmod 755 uploads qr_codes
     ```
   - **Start Command**: 
     ```bash
     php -S 0.0.0.0:$PORT -t .
     ```

### Step 5: Configure Environment Variables
In the web service settings, add these environment variables:

1. **Database Connection** (from your database service):
   - `DB_HOST`: [Copy from database service]
   - `DB_NAME`: `pawsitive_patrol`
   - `DB_USER`: [Copy from database service]
   - `DB_PASS`: [Copy from database service]
   - `DATABASE_URL`: [Copy from database service]

2. **Application Settings**:
   - `APP_URL`: `https://your-app-name.onrender.com`
   - `DEBUG_MODE`: `0`

### Step 6: Deploy and Migrate
1. Click "Deploy" - Render will build and deploy your app
2. Once deployed, visit: `https://your-app-name.onrender.com/migrate.php`
3. This will set up your database tables
4. After migration, visit: `https://your-app-name.onrender.com`

---

## 🔧 Important Notes

### Database Conversion (MySQL to PostgreSQL)
Since Render's free plan uses PostgreSQL, you may need to convert your MySQL database schema:

1. **Update SQL file for PostgreSQL**:
   - Change `AUTO_INCREMENT` to `SERIAL`
   - Change `DATETIME` to `TIMESTAMP`
   - Update any MySQL-specific syntax

2. **Alternative**: Use Render's paid MySQL plan ($7/month)

### Free Plan Limitations
- **Sleep after 15 minutes** of inactivity
- **750 hours/month** (enough for most personal projects)
- **500MB disk space**
- **100GB bandwidth/month**

### File Uploads
- Uploads are **temporary** on free plan
- Files are deleted when service restarts
- For persistent storage, upgrade to paid plan or use external storage (AWS S3)

---

## 🛠️ Troubleshooting

### Common Issues

**Build Failed:**
- Check build logs in Render dashboard
- Ensure all files are committed to GitHub
- Verify PHP syntax errors

**Database Connection Failed:**
- Double-check environment variables
- Ensure database service is running
- Verify database credentials

**App Not Loading:**
- Check service logs in Render dashboard
- Verify start command is correct
- Ensure PORT environment variable is used

**Migration Failed:**
- Visit `/migrate.php` after deployment
- Check database permissions
- Verify SQL file exists and is valid

### Getting Help
- Check Render's [PHP documentation](https://render.com/docs/deploy-php)
- Review service logs in Render dashboard
- Visit Render's community forum

---

## 📈 Next Steps

### After Successful Deployment:
1. **Test all features** (registration, login, pet management)
2. **Set up custom domain** (optional, available on paid plans)
3. **Monitor usage** in Render dashboard
4. **Set up backups** for your database

### Scaling Options:
- **Upgrade to paid plan** for persistent storage
- **Add custom domain** ($0/month on paid plans)
- **Increase resources** as needed
- **Add monitoring** and alerts

Your pet tracking app should now be live at: `https://your-app-name.onrender.com`! 🎉
