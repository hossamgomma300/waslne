# دليل تثبيت منصة وصلني - Waslne Installation Guide

## 🚗 منصة مشاركة الرحلات المصرية

### متطلبات النظام

#### متطلبات الخادم الأساسية:
- **PHP**: الإصدار 8.1 أو أحدث
- **MySQL**: الإصدار 5.7 أو أحدث (أو MariaDB 10.3+)
- **Apache/Nginx**: خادم ويب
- **Composer**: لإدارة حزم PHP
- **Node.js & NPM**: لبناء الأصول الأمامية

#### إضافات PHP المطلوبة:
```
- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo
- GD
- cURL
```

#### أذونات المجلدات:
```
storage/ - 755 (قابل للكتابة)
bootstrap/cache/ - 755 (قابل للكتابة)
public/ - 755
```

---

## 🔧 طرق التثبيت

### الطريقة الأولى: التثبيت التلقائي (موصى بها)

1. **رفع الملفات للخادم**
   ```bash
   # رفع جميع ملفات المشروع إلى مجلد الموقع
   # تأكد من رفع الملفات المخفية مثل .htaccess
   ```

2. **تشغيل معالج التثبيت**
   - افتح المتصفح واذهب إلى: `http://yourdomain.com/install.php`
   - اتبع الخطوات المعروضة في المعالج

3. **إكمال التثبيت**
   - املأ جميع البيانات المطلوبة
   - انتظر حتى اكتمال التثبيت
   - احذف ملف `install.php` بعد التثبيت

### الطريقة الثانية: التثبيت اليدوي

#### 1. إعداد قاعدة البيانات
```sql
CREATE DATABASE waslne CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'waslne_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON waslne.* TO 'waslne_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 2. إعداد ملف البيئة
```bash
# نسخ ملف البيئة
cp .env.example .env

# تعديل الإعدادات في .env
nano .env
```

#### 3. تثبيت التبعيات
```bash
# تثبيت حزم PHP
composer install --optimize-autoloader --no-dev

# تثبيت حزم Node.js
npm install

# بناء الأصول
npm run build
```

#### 4. إعداد Laravel
```bash
# توليد مفتاح التطبيق
php artisan key:generate

# تشغيل المايجريشن
php artisan migrate --force

# إنشاء الروابط التخزينية
php artisan storage:link

# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 5. إنشاء حساب المدير
```bash
php artisan db:seed --class=AdminSeeder
```

---

## ⚙️ إعدادات الخادم

### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/waslne/public
    
    <Directory /path/to/waslne/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/waslne_error.log
    CustomLog ${APACHE_LOG_DIR}/waslne_access.log combined
</VirtualHost>
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/waslne/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## 🔐 الأمان والحماية

### إعدادات الأمان الأساسية:

1. **حماية ملف .env**
   ```apache
   # في .htaccess
   <Files .env>
       Order allow,deny
       Deny from all
   </Files>
   ```

2. **تحديث كلمات المرور الافتراضية**
   - غير كلمة مرور قاعدة البيانات
   - غير كلمة مرور حساب المدير
   - استخدم كلمات مرور قوية

3. **تحديث الأذونات**
   ```bash
   # أذونات المجلدات
   find /path/to/waslne -type d -exec chmod 755 {} \;
   
   # أذونات الملفات
   find /path/to/waslne -type f -exec chmod 644 {} \;
   
   # أذونات خاصة
   chmod -R 775 storage bootstrap/cache
   ```

4. **إعداد HTTPS**
   - احصل على شهادة SSL
   - أعد توجيه HTTP إلى HTTPS
   - حدث APP_URL في .env

---

## 🌐 الخدمات الخارجية

### خرائط جوجل (مطلوبة)
1. اذهب إلى [Google Cloud Console](https://console.cloud.google.com/)
2. أنشئ مشروع جديد
3. فعل APIs التالية:
   - Maps JavaScript API
   - Places API
   - Directions API
   - Geocoding API
4. أنشئ API Key وأضفه في .env

### Firebase (للإشعارات الفورية)
1. اذهب إلى [Firebase Console](https://console.firebase.google.com/)
2. أنشئ مشروع جديد
3. فعل Cloud Messaging
4. احصل على Server Key وأضفه في .env

### Paymob (بوابة الدفع المصرية)
1. سجل في [Paymob](https://accept.paymob.com/)
2. احصل على API Key و Integration ID
3. أضف البيانات في .env

---

## 📱 إعداد التطبيق المحمول

### Android App
1. حدث `VITE_APP_URL` في .env
2. أعد بناء الأصول: `npm run build`
3. تأكد من إعداد Firebase للإشعارات

### iOS App
1. أضف domain في Apple Developer Console
2. حدث إعدادات Push Notifications
3. اختبر الاتصال مع API

---

## 🔧 استكشاف الأخطاء

### مشاكل شائعة وحلولها:

#### خطأ 500 - Internal Server Error
```bash
# تحقق من سجلات الأخطاء
tail -f storage/logs/laravel.log

# تأكد من الأذونات
chmod -R 775 storage bootstrap/cache

# امسح الكاش
php artisan cache:clear
php artisan config:clear
```

#### مشكلة في قاعدة البيانات
```bash
# تحقق من الاتصال
php artisan tinker
DB::connection()->getPdo();

# أعد تشغيل المايجريشن
php artisan migrate:fresh --seed
```

#### مشكلة في الأصول (CSS/JS)
```bash
# أعد بناء الأصول
npm run build

# تحقق من الروابط
php artisan storage:link
```

#### مشكلة في الإشعارات
```bash
# تحقق من إعدادات Queue
php artisan queue:work

# اختبر الإشعارات
php artisan tinker
Notification::route('mail', 'test@example.com')->notify(new TestNotification());
```

---

## 📊 مراقبة الأداء

### إعداد المراقبة:

1. **سجلات النظام**
   ```bash
   # مراقبة السجلات
   tail -f storage/logs/laravel.log
   
   # تنظيف السجلات القديمة
   find storage/logs -name "*.log" -mtime +30 -delete
   ```

2. **مراقبة قاعدة البيانات**
   ```sql
   -- مراقبة الاستعلامات البطيئة
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2;
   ```

3. **مراقبة الذاكرة والمعالج**
   ```bash
   # استخدام htop لمراقبة الموارد
   htop
   
   # مراقبة مساحة القرص
   df -h
   ```

---

## 🔄 النسخ الاحتياطي

### نسخ احتياطي يومي:

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/path/to/backups"
APP_DIR="/path/to/waslne"

# نسخ احتياطي لقاعدة البيانات
mysqldump -u username -p password waslne > $BACKUP_DIR/db_$DATE.sql

# نسخ احتياطي للملفات
tar -czf $BACKUP_DIR/files_$DATE.tar.gz $APP_DIR

# حذف النسخ القديمة (أكثر من 30 يوم)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

### إعداد Cron Job:
```bash
# تحرير crontab
crontab -e

# إضافة مهمة يومية في الساعة 2 صباحاً
0 2 * * * /path/to/backup.sh
```

---

## 📞 الدعم الفني

### معلومات الاتصال:
- **البريد الإلكتروني**: support@waslne.com
- **الهاتف**: +201234567890
- **الموقع**: https://waslne.com

### الموارد المفيدة:
- [Laravel Documentation](https://laravel.com/docs)
- [PHP Manual](https://www.php.net/manual/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

---

## 📝 ملاحظات مهمة

1. **احذف ملف install.php بعد التثبيت**
2. **غير كلمات المرور الافتراضية**
3. **فعل HTTPS في الإنتاج**
4. **راقب سجلات الأخطاء بانتظام**
5. **خذ نسخ احتياطية دورية**
6. **حدث النظام بانتظام**

---

## 🎉 تم التثبيت بنجاح!

بعد إكمال التثبيت، يمكنك الوصول إلى:

- **الموقع الرئيسي**: `http://yourdomain.com`
- **لوحة الإدارة**: `http://yourdomain.com/admin`
- **API Documentation**: `http://yourdomain.com/api/documentation`

**مبروك! منصة وصلني جاهزة للاستخدام** 🚗✨