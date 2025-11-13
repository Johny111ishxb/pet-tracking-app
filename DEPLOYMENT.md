# Deployment Guide for Pet Tracking Application

## Quick Start - Shared Hosting (Recommended)

### Step 1: Choose a Hosting Provider
**Recommended PHP hosting providers:**
- **Hostinger** ($2.99/month) - Great for beginners
- **Bluehost** ($3.95/month) - WordPress optimized but supports PHP
- **SiteGround** ($3.99/month) - Excellent performance
- **Namecheap** ($2.88/month) - Budget-friendly

### Step 2: Upload Files
1. **Via File Manager (easiest):**
   - Login to your hosting control panel (cPanel)
   - Open File Manager
   - Navigate to `public_html` folder
   - Upload all files from your project folder

2. **Via FTP:**
   - Use FileZilla or similar FTP client
   - Connect using credentials from your hosting provider
   - Upload all files to `public_html` directory

### Step 3: Create Database
1. In cPanel, click "MySQL Databases"
2. Create database named `pawsitive_patrol`
3. Create a database user and assign to the database
4. Note the database name, username, and password

### Step 4: Import Database Schema
1. In cPanel, click "phpMyAdmin"
2. Select your `pawsitive_patrol` database
3. Click "Import" tab
4. Upload `db/pawsitive_patrol.sql` file
5. Click "Go" to import

### Step 5: Configure Database Connection
1. Edit `db/db_connect.php` with your hosting database details:
   ```php
   $host = 'localhost'; // Usually localhost
   $dbname = 'your_username_pawsitive_patrol'; // Often prefixed with username
   $username = 'your_db_username';
   $password = 'your_db_password';
   ```

### Step 6: Set Permissions
Ensure these directories are writable (755 or 777):
- `uploads/`
- `qr_codes/`

### Step 7: Test Your Application
Visit your domain - you should see the login page!

---

## Alternative Deployment Options

### Option 1: Heroku (Free Tier Available)
1. Install Heroku CLI
2. Create `Procfile`:
   ```
   web: vendor/bin/heroku-php-apache2
   ```
3. Convert to PostgreSQL (MySQL not free on Heroku)
4. Deploy with Git

### Option 2: DigitalOcean App Platform
1. Connect your GitHub repository
2. Configure build settings for PHP
3. Add MySQL managed database
4. Deploy automatically

### Option 3: AWS Lightsail
1. Create a LAMP stack instance
2. Upload files via SFTP
3. Configure MySQL database
4. Point domain to instance

---

## Production Checklist

### Security
- [ ] Update all default passwords
- [ ] Enable HTTPS/SSL certificate
- [ ] Restrict file upload types
- [ ] Set proper directory permissions
- [ ] Remove debug information from production

### Performance
- [ ] Enable PHP OPcache
- [ ] Configure proper error logging
- [ ] Set up database backups
- [ ] Monitor resource usage

### Maintenance
- [ ] Set up regular backups
- [ ] Monitor error logs
- [ ] Keep PHP and database updated
- [ ] Test upload functionality

---

## Troubleshooting

### Common Issues

**Database Connection Failed:**
- Check database credentials in `db/db_connect.php`
- Ensure database exists and user has proper permissions
- Verify MySQL service is running

**File Upload Not Working:**
- Check directory permissions on `uploads/` folder
- Verify PHP upload limits in hosting control panel
- Ensure disk space is available

**QR Codes Not Generating:**
- Check `qr_codes/` directory permissions
- Verify GD extension is enabled in PHP
- Check error logs for specific issues

**Page Not Loading:**
- Check for PHP syntax errors in error logs
- Verify all required files are uploaded
- Check file path references

### Getting Help
- Check your hosting provider's documentation
- Review PHP error logs in cPanel
- Contact hosting support for server-specific issues
