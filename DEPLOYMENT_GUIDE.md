# 🚀 Hospital Management System - Deployment Guide

## 📋 Table of Contents
1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Testing](#testing)
6. [Production Deployment](#production-deployment)
7. [Maintenance](#maintenance)
8. [Troubleshooting](#troubleshooting)

## 🖥️ System Requirements

### Minimum Requirements:
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Storage**: 500MB free space
- **Memory**: 512MB RAM minimum (1GB+ recommended)

### PHP Extensions Required:
- `mysqli` or `pdo_mysql`
- `session`
- `json`
- `mbstring`
- `openssl`

## 📦 Installation Steps

### 1. Download and Extract
```bash
# Download the system files
# Extract to your web server directory
# Example for XAMPP:
C:\xampp\htdocs\Hospital_managment_system\
```

### 2. Set File Permissions
```bash
# For Linux/Unix systems:
chmod 755 /path/to/hospital_system/
chmod 644 /path/to/hospital_system/*.php
chmod 666 /path/to/hospital_system/config/
```

### 3. Web Server Configuration
```apache
# Apache .htaccess (already included)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

## 🗄️ Database Setup

### 1. Create Database
```sql
CREATE DATABASE hospital_management_system;
USE hospital_management_system;
```

### 2. Import Database Schema
```bash
# Method 1: Using phpMyAdmin
# - Open phpMyAdmin
# - Select hospital_management_system database
# - Import database/hospital.sql

# Method 2: Using MySQL command line
mysql -u root -p hospital_management_system < database/hospital.sql
```

### 3. Verify Database Setup
Run the setup script:
```
http://localhost/Hospital_managment_system/complete_setup.php
```

## ⚙️ Configuration

### 1. Database Configuration
Edit `config/db.php`:
```php
$servername = "localhost";
$username = "root";        // Your MySQL username
$password = "";            // Your MySQL password
$dbname = "hospital_management_system";
```

### 2. System Configuration
- Update timezone settings
- Configure email settings (if needed)
- Set up backup schedules
- Configure security settings

## 🧪 Testing

### 1. Run System Tests
```
http://localhost/Hospital_managment_system/system_status_check.php
```

### 2. Test User Accounts
Default test accounts (password: 123456):
- **Admin**: admin / 123456
- **Doctor**: doctor1 / 123456
- **Nurse**: nurse1 / 123456
- **Receptionist**: receptionist1 / 123456
- **Pharmacist**: pharmacist1 / 123456
- **Accountant**: accountant1 / 123456
- **Lab Tech**: labtech1 / 123456

### 3. Test Patient Registration
```
http://localhost/Hospital_managment_system/patient_self_register.php
```

### 4. Functional Testing Checklist
- [ ] User login/logout
- [ ] Patient registration
- [ ] Appointment booking
- [ ] Prescription management
- [ ] Billing system
- [ ] Insurance claims
- [ ] Reports generation
- [ ] Data backup/restore

## 🌐 Production Deployment

### 1. Security Hardening
```php
// In production, update config/db.php:
error_reporting(0);
ini_set('display_errors', 0);

// Enable HTTPS
$secure_connection = true;
```

### 2. Environment Setup
- Use strong database passwords
- Enable SSL/TLS certificates
- Configure firewall rules
- Set up regular backups
- Enable logging

### 3. Performance Optimization
- Enable PHP OPcache
- Configure database indexing
- Set up CDN (if needed)
- Optimize images and assets
- Enable gzip compression

### 4. Backup Strategy
```bash
# Daily database backup
mysqldump -u root -p hospital_management_system > backup_$(date +%Y%m%d).sql

# Weekly full system backup
tar -czf hospital_system_backup_$(date +%Y%m%d).tar.gz /path/to/hospital_system/
```

## 🔧 Maintenance

### Daily Tasks
- Monitor system logs
- Check database performance
- Verify backup completion
- Review security alerts

### Weekly Tasks
- Update system statistics
- Clean temporary files
- Review user accounts
- Test backup restoration

### Monthly Tasks
- Security updates
- Performance optimization
- User training updates
- System documentation review

## 🚨 Troubleshooting

### Common Issues

#### 1. Database Connection Error
```
Error: Connection failed: Access denied for user 'root'@'localhost'
```
**Solution**: Check database credentials in `config/db.php`

#### 2. Blank Pages
**Symptoms**: Pages load but show nothing
**Solution**: 
- Check PHP error logs
- Verify file permissions
- Enable error reporting temporarily

#### 3. Session Issues
**Symptoms**: Users get logged out frequently
**Solution**:
- Check session configuration
- Verify server time settings
- Clear browser cache

#### 4. File Upload Issues
**Solution**:
- Check PHP upload limits
- Verify directory permissions
- Review server storage space

### Debug Mode
Enable debug mode by adding to any PHP file:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Log Files
Check these locations for error logs:
- `/var/log/apache2/error.log` (Linux)
- `C:\xampp\apache\logs\error.log` (Windows XAMPP)
- PHP error log (location varies)

## 📞 Support

### Getting Help
1. Check this documentation first
2. Review `TESTING_GUIDE.md`
3. Check system logs
4. Contact system administrator

### Reporting Issues
When reporting issues, include:
- Error messages (exact text)
- Steps to reproduce
- Browser and version
- Server environment details
- Screenshots (if applicable)

## 📈 System Monitoring

### Key Metrics to Monitor
- Database performance
- Server response times
- User activity logs
- Error rates
- Storage usage
- Backup success rates

### Recommended Tools
- **Database**: phpMyAdmin, MySQL Workbench
- **Monitoring**: Server monitoring tools
- **Logs**: Log analysis tools
- **Backup**: Automated backup solutions

## 🔄 Updates and Upgrades

### Before Updating
1. Create full system backup
2. Test in staging environment
3. Review changelog
4. Plan rollback strategy

### Update Process
1. Put system in maintenance mode
2. Backup current version
3. Deploy new version
4. Run database migrations
5. Test functionality
6. Remove maintenance mode

---

## 📋 Quick Start Checklist

- [ ] Install web server (Apache/Nginx)
- [ ] Install PHP 7.4+
- [ ] Install MySQL/MariaDB
- [ ] Create database
- [ ] Import database schema
- [ ] Configure database connection
- [ ] Set file permissions
- [ ] Test system access
- [ ] Create admin account
- [ ] Configure security settings
- [ ] Set up backups
- [ ] Test all modules
- [ ] Train users
- [ ] Go live!

---

**System Version**: 1.0.0  
**Last Updated**: January 2026  
**Documentation**: Complete Hospital Management System