# Pet Tracking Application

A PHP-based pet tracking system that allows pet owners to register their pets, generate QR codes, and track lost pets.

## Features
- Pet registration and management
- QR code generation for pet identification
- Lost pet tracking and reporting
- Owner dashboard
- Pet information scanning via QR codes

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- GD extension for QR code generation

## Installation

### Local Development
1. Clone this repository
2. Copy `.env.example` to `.env` and configure database settings
3. Import the database schema from `db/pawsitive_patrol.sql`
4. Configure your web server to point to the project directory
5. Ensure `uploads/` and `qr_codes/` directories are writable

### Database Setup
1. Create a MySQL database named `pawsitive_patrol`
2. Import the SQL file: `mysql -u username -p pawsitive_patrol < db/pawsitive_patrol.sql`

## Deployment

### Shared Hosting (Recommended for beginners)
1. Upload all files to your hosting account's public_html directory
2. Create a MySQL database through your hosting control panel
3. Import the database schema from `db/pawsitive_patrol.sql`
4. Update `db/db_connect.php` with your hosting database credentials
5. Ensure `uploads/` and `qr_codes/` directories have write permissions (755 or 777)

### Cloud Hosting Options
- **Heroku**: Requires PostgreSQL adapter
- **DigitalOcean App Platform**: Direct PHP support
- **AWS Lightsail**: Full LAMP stack support

## File Structure
- `login.php` - User authentication
- `register.php` - User registration
- `owner_dashboard.php` - Main dashboard
- `includes/` - Core functionality modules
- `db/` - Database connection and schema
- `css/` - Stylesheets
- `uploads/` - User uploaded pet photos
- `qr_codes/` - Generated QR codes

## Security Notes
- Never commit `.env` files to version control
- Ensure upload directories are secured
- Use HTTPS in production
- Regularly update PHP and dependencies
