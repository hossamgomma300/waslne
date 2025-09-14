#!/bin/bash

# Waslne Server Setup Script
# Script to setup Waslne Egyptian Ride Sharing Platform on Ubuntu/CentOS server

echo "🚗 مرحباً بك في معالج إعداد خادم وصلني"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}$1${NC}"
}

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$NAME
    VER=$VERSION_ID
else
    print_error "Cannot detect OS version"
    exit 1
fi

print_status "Detected OS: $OS $VER"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   exit 1
fi

# Update system
print_header "تحديث النظام..."
if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
    apt update && apt upgrade -y
    PACKAGE_MANAGER="apt"
elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]]; then
    yum update -y
    PACKAGE_MANAGER="yum"
else
    print_error "Unsupported OS: $OS"
    exit 1
fi

# Install required packages
print_header "تثبيت الحزم المطلوبة..."

if [[ "$PACKAGE_MANAGER" == "apt" ]]; then
    apt install -y curl wget git unzip software-properties-common
    
    # Add PHP repository
    add-apt-repository ppa:ondrej/php -y
    apt update
    
    # Install PHP 8.1 and extensions
    apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-gd php8.1-curl php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-json php8.1-tokenizer php8.1-ctype php8.1-fileinfo
    
    # Install Nginx
    apt install -y nginx
    
    # Install MySQL
    apt install -y mysql-server
    
    # Install Node.js
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt install -y nodejs
    
elif [[ "$PACKAGE_MANAGER" == "yum" ]]; then
    yum install -y curl wget git unzip epel-release
    
    # Install Remi repository for PHP
    yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
    yum module enable php:remi-8.1 -y
    
    # Install PHP 8.1 and extensions
    yum install -y php php-fpm php-mysql php-xml php-gd php-curl php-mbstring php-zip php-bcmath php-json php-tokenizer php-ctype php-fileinfo
    
    # Install Nginx
    yum install -y nginx
    
    # Install MySQL
    yum install -y mysql-server
    
    # Install Node.js
    curl -fsSL https://rpm.nodesource.com/setup_18.x | bash -
    yum install -y nodejs
fi

# Install Composer
print_header "تثبيت Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Start and enable services
print_header "تشغيل الخدمات..."
systemctl start nginx
systemctl enable nginx
systemctl start mysql
systemctl enable mysql
systemctl start php8.1-fpm
systemctl enable php8.1-fpm

# Secure MySQL installation
print_header "تأمين MySQL..."
mysql_secure_installation

# Create database and user
print_header "إعداد قاعدة البيانات..."
read -p "Enter database name [waslne]: " DB_NAME
DB_NAME=${DB_NAME:-waslne}

read -p "Enter database username [waslne_user]: " DB_USER
DB_USER=${DB_USER:-waslne_user}

read -s -p "Enter database password: " DB_PASS
echo

mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

# Create web directory
print_header "إعداد مجلد الموقع..."
read -p "Enter domain name (e.g., waslne.com): " DOMAIN
read -p "Enter web directory [/var/www/${DOMAIN}]: " WEB_DIR
WEB_DIR=${WEB_DIR:-/var/www/${DOMAIN}}

mkdir -p $WEB_DIR
chown -R www-data:www-data $WEB_DIR
chmod -R 755 $WEB_DIR

# Configure Nginx
print_header "إعداد Nginx..."
cat > /etc/nginx/sites-available/$DOMAIN << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $WEB_DIR/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location /storage {
        alias $WEB_DIR/storage/app/public;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\.(git|svn|hg) {
        deny all;
    }

    location ~ /(vendor|tests|database|node_modules) {
        deny all;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
nginx -t
if [ $? -eq 0 ]; then
    systemctl reload nginx
    print_status "Nginx configuration successful"
else
    print_error "Nginx configuration failed"
    exit 1
fi

# Configure PHP-FPM
print_header "إعداد PHP-FPM..."
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.1/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/' /etc/php/8.1/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 100M/' /etc/php/8.1/fpm/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.1/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.1/fpm/php.ini

systemctl restart php8.1-fpm

# Setup SSL with Let's Encrypt
print_header "إعداد SSL..."
read -p "Do you want to setup SSL with Let's Encrypt? (y/n): " SETUP_SSL

if [[ "$SETUP_SSL" == "y" || "$SETUP_SSL" == "Y" ]]; then
    if [[ "$PACKAGE_MANAGER" == "apt" ]]; then
        apt install -y certbot python3-certbot-nginx
    else
        yum install -y certbot python3-certbot-nginx
    fi
    
    certbot --nginx -d $DOMAIN -d www.$DOMAIN
    
    # Setup auto-renewal
    (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -
fi

# Setup firewall
print_header "إعداد الجدار الناري..."
if command -v ufw &> /dev/null; then
    ufw allow 22
    ufw allow 80
    ufw allow 443
    ufw --force enable
elif command -v firewall-cmd &> /dev/null; then
    firewall-cmd --permanent --add-service=ssh
    firewall-cmd --permanent --add-service=http
    firewall-cmd --permanent --add-service=https
    firewall-cmd --reload
fi

# Create deployment script
print_header "إنشاء سكريبت النشر..."
cat > $WEB_DIR/deploy.sh << 'EOF'
#!/bin/bash

# Waslne Deployment Script
echo "🚗 بدء نشر وصلني..."

# Pull latest changes
git pull origin main

# Install/Update dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart services
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx

echo "✅ تم النشر بنجاح!"
EOF

chmod +x $WEB_DIR/deploy.sh

# Create backup script
print_header "إنشاء سكريبت النسخ الاحتياطي..."
mkdir -p /opt/waslne-backups

cat > /opt/waslne-backups/backup.sh << EOF
#!/bin/bash

# Waslne Backup Script
DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/waslne-backups"
WEB_DIR="$WEB_DIR"
DB_NAME="$DB_NAME"
DB_USER="$DB_USER"

# Create backup directory
mkdir -p \$BACKUP_DIR/\$DATE

# Backup database
mysqldump -u \$DB_USER -p\$DB_PASS \$DB_NAME > \$BACKUP_DIR/\$DATE/database.sql

# Backup files
tar -czf \$BACKUP_DIR/\$DATE/files.tar.gz -C \$WEB_DIR .

# Remove old backups (keep last 7 days)
find \$BACKUP_DIR -type d -mtime +7 -exec rm -rf {} +

echo "Backup completed: \$BACKUP_DIR/\$DATE"
EOF

chmod +x /opt/waslne-backups/backup.sh

# Setup cron job for backups
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/waslne-backups/backup.sh") | crontab -

# Create log rotation
print_header "إعداد تدوير السجلات..."
cat > /etc/logrotate.d/waslne << EOF
$WEB_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 644 www-data www-data
}
EOF

# Final instructions
print_header "تم إعداد الخادم بنجاح! 🎉"
echo "=================================================="
print_status "معلومات الخادم:"
echo "Domain: $DOMAIN"
echo "Web Directory: $WEB_DIR"
echo "Database: $DB_NAME"
echo "Database User: $DB_USER"
echo ""
print_status "الخطوات التالية:"
echo "1. رفع ملفات وصلني إلى: $WEB_DIR"
echo "2. تشغيل: cd $WEB_DIR && composer install"
echo "3. إنشاء ملف .env وتعديل الإعدادات"
echo "4. تشغيل: php artisan key:generate"
echo "5. تشغيل: php artisan migrate"
echo "6. تشغيل: php artisan storage:link"
echo "7. زيارة: http://$DOMAIN/install.php"
echo ""
print_warning "لا تنس:"
echo "- تغيير كلمات المرور الافتراضية"
echo "- إعداد النسخ الاحتياطية"
echo "- مراقبة السجلات"
echo ""
print_status "ملفات مفيدة:"
echo "- سكريبت النشر: $WEB_DIR/deploy.sh"
echo "- سكريبت النسخ الاحتياطي: /opt/waslne-backups/backup.sh"
echo "- إعدادات Nginx: /etc/nginx/sites-available/$DOMAIN"
echo ""
echo "🚗 وصلني جاهز للانطلاق! ✨"