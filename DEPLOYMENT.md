# Waslne Deployment Guide

This guide provides step-by-step instructions for deploying the Waslne ride-sharing platform to production.

## 🚀 Pre-Deployment Checklist

### Server Requirements
- [ ] Ubuntu 20.04+ or CentOS 8+ server
- [ ] PHP 8.1+ with required extensions
- [ ] MySQL 8.0+ or MariaDB 10.4+
- [ ] Redis 6.0+
- [ ] Nginx or Apache web server
- [ ] SSL certificate
- [ ] Domain name configured

### Required PHP Extensions
```bash
sudo apt install php8.1-cli php8.1-fpm php8.1-mysql php8.1-redis php8.1-xml php8.1-curl php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl
```

### Third-Party Service Accounts
- [ ] Google Maps API key
- [ ] Paymob merchant account
- [ ] Fawry merchant account
- [ ] Firebase project for FCM
- [ ] SMS provider account (Nexmo/Twilio)
- [ ] Pusher account (for real-time features)

## 🔧 Server Setup

### 1. Install Dependencies

#### Ubuntu/Debian
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.1 php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis php8.1-xml php8.1-curl php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install MySQL
sudo apt install mysql-server
sudo mysql_secure_installation

# Install Redis
sudo apt install redis-server
sudo systemctl enable redis-server

# Install Nginx
sudo apt install nginx
sudo systemctl enable nginx
```

### 2. Create Database
```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE waslne CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'waslne_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON waslne.* TO 'waslne_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Create Application User
```bash
sudo adduser waslne
sudo usermod -aG www-data waslne
```

## 📁 Application Deployment

### 1. Clone Repository
```bash
sudo su - waslne
cd /var/www
git clone https://github.com/your-username/waslne.git
cd waslne
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm ci --only=production

# Build assets
npm run build
```

### 3. Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Edit environment file
nano .env
```

#### Production Environment Variables
```env
APP_NAME="Waslne"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=waslne
DB_USERNAME=waslne_user
DB_PASSWORD=secure_password_here

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Add all your API keys and service configurations here
```

### 4. Generate Keys and Run Migrations
```bash
# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --class=AdminSeeder

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Set Permissions
```bash
sudo chown -R waslne:www-data /var/www/waslne
sudo chmod -R 755 /var/www/waslne
sudo chmod -R 775 /var/www/waslne/storage
sudo chmod -R 775 /var/www/waslne/bootstrap/cache
```

## 🌐 Web Server Configuration

### Nginx Configuration
Create `/etc/nginx/sites-available/waslne`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/waslne/public;

    index index.php index.html index.htm;

    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Increase upload size for driver documents
    client_max_body_size 10M;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/waslne /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🔄 Process Management

### 1. Queue Workers with Supervisor
Create `/etc/supervisor/conf.d/waslne-worker.conf`:

```ini
[program:waslne-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/waslne/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=waslne
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/waslne/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start waslne-worker:*
```

### 2. Laravel Scheduler
Add to crontab for waslne user:
```bash
sudo crontab -u waslne -e
```

Add this line:
```bash
* * * * * cd /var/www/waslne && php artisan schedule:run >> /dev/null 2>&1
```

### 3. WebSocket Server (Optional)
If using Laravel WebSockets instead of Pusher:

Create `/etc/supervisor/conf.d/waslne-websockets.conf`:
```ini
[program:waslne-websockets]
command=php /var/www/waslne/artisan websockets:serve
autostart=true
autorestart=true
user=waslne
redirect_stderr=true
stdout_logfile=/var/www/waslne/storage/logs/websockets.log
```

## 🔒 SSL Certificate

### Using Let's Encrypt (Certbot)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Auto-renewal
```bash
sudo crontab -e
```

Add:
```bash
0 12 * * * /usr/bin/certbot renew --quiet
```

## 📊 Monitoring & Logging

### 1. Log Rotation
Create `/etc/logrotate.d/waslne`:

```
/var/www/waslne/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 waslne waslne
}
```

### 2. System Monitoring
Install monitoring tools:
```bash
sudo apt install htop iotop nethogs
```

### 3. Application Monitoring
Consider using:
- **Sentry** for error tracking
- **New Relic** or **DataDog** for APM
- **Uptime Robot** for uptime monitoring

## 🔧 Performance Optimization

### 1. PHP-FPM Optimization
Edit `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### 2. MySQL Optimization
Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 128M
query_cache_type = 1
```

### 3. Redis Optimization
Edit `/etc/redis/redis.conf`:

```ini
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## 🚀 Deployment Script

Create `deploy.sh` for automated deployments:

```bash
#!/bin/bash

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci --only=production

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart waslne-worker:*
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx

echo "Deployment completed!"
```

Make it executable:
```bash
chmod +x deploy.sh
```

## 🔍 Health Checks

### 1. Application Health Check
Create a monitoring endpoint:
```bash
curl https://yourdomain.com/api/health
```

### 2. Database Connection
```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

### 3. Queue Status
```bash
php artisan queue:monitor
```

### 4. Redis Connection
```bash
redis-cli ping
```

## 🆘 Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
sudo chown -R waslne:www-data /var/www/waslne
sudo chmod -R 775 /var/www/waslne/storage
sudo chmod -R 775 /var/www/waslne/bootstrap/cache
```

#### 2. Queue Not Processing
```bash
sudo supervisorctl restart waslne-worker:*
php artisan queue:restart
```

#### 3. High Memory Usage
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 4. SSL Issues
```bash
sudo certbot renew --dry-run
sudo nginx -t
```

### Log Files to Check
- Application: `/var/www/waslne/storage/logs/laravel.log`
- Nginx: `/var/log/nginx/error.log`
- PHP-FPM: `/var/log/php8.1-fpm.log`
- MySQL: `/var/log/mysql/error.log`
- Supervisor: `/var/log/supervisor/supervisord.log`

## 📋 Post-Deployment Checklist

- [ ] Application loads correctly
- [ ] Database connection working
- [ ] Redis connection working
- [ ] Queue workers running
- [ ] Cron jobs scheduled
- [ ] SSL certificate valid
- [ ] Payment gateways configured
- [ ] SMS service working
- [ ] Push notifications working
- [ ] Maps API working
- [ ] Admin panel accessible
- [ ] File uploads working
- [ ] Email notifications working
- [ ] Backup system configured
- [ ] Monitoring tools configured

## 🔄 Backup Strategy

### 1. Database Backup
```bash
#!/bin/bash
# backup-db.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u waslne_user -p waslne > /backups/waslne_db_$DATE.sql
gzip /backups/waslne_db_$DATE.sql
find /backups -name "waslne_db_*.sql.gz" -mtime +7 -delete
```

### 2. File Backup
```bash
#!/bin/bash
# backup-files.sh
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backups/waslne_files_$DATE.tar.gz /var/www/waslne/storage/app/public
find /backups -name "waslne_files_*.tar.gz" -mtime +7 -delete
```

### 3. Automated Backups
Add to crontab:
```bash
0 2 * * * /path/to/backup-db.sh
0 3 * * * /path/to/backup-files.sh
```

---

**Need help?** Contact support at support@waslne.com