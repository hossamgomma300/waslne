<?php
/**
 * Waslne - وصلني
 * Egyptian Ride Sharing Platform Installation Script
 * 
 * This script will guide you through the installation process
 * and setup your Waslne platform on your server.
 */

// Prevent direct access if already installed
if (file_exists('.env') && !isset($_GET['force'])) {
    die('Application is already installed. Add ?force=1 to reinstall.');
}

// Start session for installation steps
session_start();

// Installation steps
$steps = [
    1 => 'متطلبات النظام',
    2 => 'إعدادات قاعدة البيانات',
    3 => 'معلومات الموقع',
    4 => 'حساب المدير',
    5 => 'إعدادات إضافية',
    6 => 'التثبيت النهائي'
];

$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($current_step) {
        case 2:
            $_SESSION['db_config'] = $_POST;
            if (testDatabaseConnection($_POST)) {
                header('Location: install.php?step=3');
                exit;
            } else {
                $error = 'فشل في الاتصال بقاعدة البيانات. تحقق من البيانات المدخلة.';
            }
            break;
        case 3:
            $_SESSION['site_config'] = $_POST;
            header('Location: install.php?step=4');
            exit;
        case 4:
            $_SESSION['admin_config'] = $_POST;
            header('Location: install.php?step=5');
            exit;
        case 5:
            $_SESSION['additional_config'] = $_POST;
            header('Location: install.php?step=6');
            exit;
        case 6:
            if (performInstallation()) {
                $success = true;
            } else {
                $error = 'حدث خطأ أثناء التثبيت. تحقق من الأذونات والإعدادات.';
            }
            break;
    }
}

function testDatabaseConnection($config) {
    try {
        $pdo = new PDO(
            "mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",
            $config['db_username'],
            $config['db_password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Try to create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function performInstallation() {
    try {
        // Create .env file
        createEnvFile();
        
        // Generate application key
        generateAppKey();
        
        // Run migrations
        runMigrations();
        
        // Create admin user
        createAdminUser();
        
        // Set permissions
        setPermissions();
        
        // Clear caches
        clearCaches();
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function createEnvFile() {
    $db = $_SESSION['db_config'];
    $site = $_SESSION['site_config'];
    $admin = $_SESSION['admin_config'];
    $additional = $_SESSION['additional_config'] ?? [];
    
    $env_content = "APP_NAME=\"{$site['app_name']}\"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL={$site['app_url']}
APP_TIMEZONE=Africa/Cairo
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_CURRENCY=EGP

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration
DB_CONNECTION=mysql
DB_HOST={$db['db_host']}
DB_PORT={$db['db_port']}
DB_DATABASE={$db['db_database']}
DB_USERNAME={$db['db_username']}
DB_PASSWORD={$db['db_password']}

# Broadcast Configuration
BROADCAST_DRIVER=pusher
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Redis Configuration (Optional)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST={$additional['mail_host'] ?? 'smtp.gmail.com'}
MAIL_PORT={$additional['mail_port'] ?? '587'}
MAIL_USERNAME={$additional['mail_username'] ?? ''}
MAIL_PASSWORD={$additional['mail_password'] ?? ''}
MAIL_ENCRYPTION={$additional['mail_encryption'] ?? 'tls'}
MAIL_FROM_ADDRESS=\"{$site['support_email']}\"
MAIL_FROM_NAME=\"\${APP_NAME}\"

# JWT Configuration
JWT_SECRET=
JWT_TTL=1440
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

# Pusher Configuration (Real-time updates)
PUSHER_APP_ID={$additional['pusher_app_id'] ?? ''}
PUSHER_APP_KEY={$additional['pusher_app_key'] ?? ''}
PUSHER_APP_SECRET={$additional['pusher_app_secret'] ?? ''}
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

# Google Maps API
GOOGLE_MAPS_API_KEY={$additional['google_maps_key'] ?? ''}
GOOGLE_PLACES_API_KEY={$additional['google_places_key'] ?? ''}
GOOGLE_DIRECTIONS_API_KEY={$additional['google_directions_key'] ?? ''}
GOOGLE_GEOCODING_API_KEY={$additional['google_geocoding_key'] ?? ''}

# Firebase Cloud Messaging (Push Notifications)
FCM_SERVER_KEY={$additional['fcm_server_key'] ?? ''}
FCM_SENDER_ID={$additional['fcm_sender_id'] ?? ''}
FCM_PROJECT_ID={$additional['fcm_project_id'] ?? ''}

# SMS Services
NEXMO_KEY={$additional['nexmo_key'] ?? ''}
NEXMO_SECRET={$additional['nexmo_secret'] ?? ''}
NEXMO_SMS_FROM=\"Waslne\"

# Egyptian SMS Providers
SMS_MISR_USERNAME={$additional['sms_misr_username'] ?? ''}
SMS_MISR_PASSWORD={$additional['sms_misr_password'] ?? ''}
SMS_MISR_SENDER=\"Waslne\"
SMS_MISR_BASE_URL=https://smsmisr.com/api

# Payment Gateways - Paymob (Egyptian)
PAYMOB_API_KEY={$additional['paymob_api_key'] ?? ''}
PAYMOB_INTEGRATION_ID={$additional['paymob_integration_id'] ?? ''}
PAYMOB_IFRAME_ID={$additional['paymob_iframe_id'] ?? ''}
PAYMOB_HMAC_SECRET={$additional['paymob_hmac_secret'] ?? ''}
PAYMOB_PUBLIC_KEY={$additional['paymob_public_key'] ?? ''}
PAYMOB_BASE_URL=https://accept.paymob.com/api

# File Storage - Local (Default)
FILESYSTEM_DISK=local

# Application Business Logic
PLATFORM_COMMISSION_RATE={$site['commission_rate'] ?? '0.15'}
DRIVER_SEARCH_RADIUS={$site['search_radius'] ?? '10'}
OFFER_EXPIRY_MINUTES=10
TRIP_TIMEOUT_MINUTES=30
MAX_OFFERS_PER_TRIP=10
MIN_TRIP_AMOUNT={$site['min_trip_amount'] ?? '10'}
MAX_TRIP_AMOUNT={$site['max_trip_amount'] ?? '1000'}

# Support Contact
SUPPORT_PHONE=\"{$site['support_phone']}\"
SUPPORT_EMAIL=\"{$site['support_email']}\"

# Vite Configuration
VITE_APP_NAME=\"\${APP_NAME}\"
VITE_PUSHER_APP_KEY=\"\${PUSHER_APP_KEY}\"
VITE_PUSHER_HOST=\"\${PUSHER_HOST}\"
VITE_PUSHER_PORT=\"\${PUSHER_PORT}\"
VITE_PUSHER_SCHEME=\"\${PUSHER_SCHEME}\"
VITE_PUSHER_APP_CLUSTER=\"\${PUSHER_APP_CLUSTER}\"
";

    file_put_contents('.env', $env_content);
}

function generateAppKey() {
    $key = 'base64:' . base64_encode(random_bytes(32));
    $env = file_get_contents('.env');
    $env = str_replace('APP_KEY=', "APP_KEY={$key}", $env);
    file_put_contents('.env', $env);
    
    // Generate JWT secret
    $jwt_secret = bin2hex(random_bytes(32));
    $env = file_get_contents('.env');
    $env = str_replace('JWT_SECRET=', "JWT_SECRET={$jwt_secret}", $env);
    file_put_contents('.env', $env);
}

function runMigrations() {
    exec('php artisan migrate --force 2>&1', $output, $return_var);
    return $return_var === 0;
}

function createAdminUser() {
    $admin = $_SESSION['admin_config'];
    
    $pdo = new PDO(
        "mysql:host={$_SESSION['db_config']['db_host']};dbname={$_SESSION['db_config']['db_database']};charset=utf8mb4",
        $_SESSION['db_config']['db_username'],
        $_SESSION['db_config']['db_password']
    );
    
    $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role, is_active, created_at, updated_at) VALUES (?, ?, ?, 'super_admin', 1, NOW(), NOW())");
    $stmt->execute([$admin['name'], $admin['email'], $hashedPassword]);
}

function setPermissions() {
    chmod('storage', 0755);
    chmod('bootstrap/cache', 0755);
    
    if (is_dir('storage/app')) chmod('storage/app', 0755);
    if (is_dir('storage/framework')) chmod('storage/framework', 0755);
    if (is_dir('storage/logs')) chmod('storage/logs', 0755);
}

function clearCaches() {
    exec('php artisan config:cache 2>&1');
    exec('php artisan route:cache 2>&1');
    exec('php artisan view:cache 2>&1');
}

function checkRequirements() {
    $requirements = [
        'PHP Version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'PDO Extension' => extension_loaded('pdo'),
        'Mbstring Extension' => extension_loaded('mbstring'),
        'Tokenizer Extension' => extension_loaded('tokenizer'),
        'XML Extension' => extension_loaded('xml'),
        'Ctype Extension' => extension_loaded('ctype'),
        'JSON Extension' => extension_loaded('json'),
        'BCMath Extension' => extension_loaded('bcmath'),
        'Fileinfo Extension' => extension_loaded('fileinfo'),
        'GD Extension' => extension_loaded('gd'),
        'cURL Extension' => extension_loaded('curl'),
    ];
    
    $permissions = [
        'storage/' => is_writable('storage'),
        'bootstrap/cache/' => is_writable('bootstrap/cache'),
        '.env' => is_writable('.') || !file_exists('.env'),
    ];
    
    return ['requirements' => $requirements, 'permissions' => $permissions];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت منصة وصلني - Waslne Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .install-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .install-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .install-body {
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #e9ecef;
            z-index: -1;
        }
        .step:last-child::after {
            display: none;
        }
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step.completed::after {
            background: #28a745;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
        }
        .step-title {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .requirement-item:last-child {
            border-bottom: none;
        }
        .status-icon {
            width: 20px;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .alert {
            border-radius: 10px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .success-animation {
            text-align: center;
            padding: 3rem 0;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
            animation: bounce 1s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <div class="logo">🚗 وصلني</div>
                <h2>تثبيت منصة مشاركة الرحلات المصرية</h2>
                <p class="mb-0">مرحباً بك في معالج تثبيت منصة وصلني</p>
            </div>
            
            <div class="install-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <?php foreach ($steps as $num => $title): ?>
                    <div class="step <?= $num < $current_step ? 'completed' : ($num == $current_step ? 'active' : '') ?>">
                        <div class="step-number">
                            <?= $num < $current_step ? '<i class="fas fa-check"></i>' : $num ?>
                        </div>
                        <div class="step-title"><?= $title ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error ?>
                </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                <div class="success-animation">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-success">تم التثبيت بنجاح!</h3>
                    <p class="text-muted">تم تثبيت منصة وصلني بنجاح على الخادم</p>
                    <div class="mt-4">
                        <a href="admin/login" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            دخول لوحة الإدارة
                        </a>
                        <a href="/" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-home me-2"></i>
                            الصفحة الرئيسية
                        </a>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded">
                        <h5>معلومات مهمة:</h5>
                        <ul class="list-unstyled mb-0">
                            <li><strong>رابط لوحة الإدارة:</strong> <code><?= $_SESSION['site_config']['app_url'] ?? '' ?>/admin</code></li>
                            <li><strong>البريد الإلكتروني للمدير:</strong> <code><?= $_SESSION['admin_config']['email'] ?? '' ?></code></li>
                            <li><strong>احرص على حذف ملف install.php من الخادم لأسباب أمنية</strong></li>
                        </ul>
                    </div>
                </div>
                <?php else: ?>

                <!-- Step 1: System Requirements -->
                <?php if ($current_step == 1): ?>
                    <?php $checks = checkRequirements(); ?>
                    <h3 class="mb-4">
                        <i class="fas fa-server me-2"></i>
                        فحص متطلبات النظام
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>متطلبات PHP:</h5>
                            <?php foreach ($checks['requirements'] as $req => $status): ?>
                            <div class="requirement-item">
                                <span><?= $req ?></span>
                                <span class="status-icon">
                                    <?= $status ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>أذونات المجلدات:</h5>
                            <?php foreach ($checks['permissions'] as $dir => $status): ?>
                            <div class="requirement-item">
                                <span><?= $dir ?></span>
                                <span class="status-icon">
                                    <?= $status ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php 
                    $all_passed = array_reduce($checks['requirements'], function($carry, $item) { return $carry && $item; }, true) &&
                                  array_reduce($checks['permissions'], function($carry, $item) { return $carry && $item; }, true);
                    ?>
                    
                    <div class="mt-4">
                        <?php if ($all_passed): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                جميع المتطلبات متوفرة! يمكنك المتابعة للخطوة التالية.
                            </div>
                            <a href="install.php?step=2" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>
                                التالي
                            </a>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                يرجى حل المشاكل المذكورة أعلاه قبل المتابعة.
                            </div>
                            <button onclick="location.reload()" class="btn btn-secondary">
                                <i class="fas fa-redo me-2"></i>
                                إعادة الفحص
                            </button>
                        <?php endif; ?>
                    </div>

                <!-- Step 2: Database Configuration -->
                <?php elseif ($current_step == 2): ?>
                    <h3 class="mb-4">
                        <i class="fas fa-database me-2"></i>
                        إعدادات قاعدة البيانات
                    </h3>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">عنوان الخادم</label>
                                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">رقم المنفذ</label>
                                    <input type="number" name="db_port" class="form-control" value="3306" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">اسم قاعدة البيانات</label>
                            <input type="text" name="db_database" class="form-control" value="waslne" required>
                            <div class="form-text">سيتم إنشاء قاعدة البيانات تلقائياً إذا لم تكن موجودة</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المستخدم</label>
                                    <input type="text" name="db_username" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور</label>
                                    <input type="password" name="db_password" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="install.php?step=1" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-2"></i>
                                السابق
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database me-2"></i>
                                اختبار الاتصال والمتابعة
                            </button>
                        </div>
                    </form>

                <!-- Step 3: Site Information -->
                <?php elseif ($current_step == 3): ?>
                    <h3 class="mb-4">
                        <i class="fas fa-globe me-2"></i>
                        معلومات الموقع
                    </h3>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">اسم الموقع</label>
                            <input type="text" name="app_name" class="form-control" value="Waslne - وصلني" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">رابط الموقع</label>
                            <input type="url" name="app_url" class="form-control" value="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">رقم الدعم الفني</label>
                                    <input type="tel" name="support_phone" class="form-control" value="+201234567890" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">بريد الدعم الفني</label>
                                    <input type="email" name="support_email" class="form-control" value="support@waslne.com" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">نسبة عمولة المنصة (%)</label>
                                    <input type="number" name="commission_rate" class="form-control" value="15" min="0" max="50" step="0.1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">أقل مبلغ رحلة (جنيه)</label>
                                    <input type="number" name="min_trip_amount" class="form-control" value="10" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">أعلى مبلغ رحلة (جنيه)</label>
                                    <input type="number" name="max_trip_amount" class="form-control" value="1000" min="100" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">نطاق البحث عن السائقين (كيلومتر)</label>
                            <input type="number" name="search_radius" class="form-control" value="10" min="1" max="50" required>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="install.php?step=2" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-2"></i>
                                السابق
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                التالي
                            </button>
                        </div>
                    </form>

                <!-- Step 4: Admin Account -->
                <?php elseif ($current_step == 4): ?>
                    <h3 class="mb-4">
                        <i class="fas fa-user-shield me-2"></i>
                        حساب مدير الموقع
                    </h3>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">اسم المدير</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور</label>
                            <input type="password" name="password" class="form-control" minlength="8" required>
                            <div class="form-text">يجب أن تكون كلمة المرور 8 أحرف على الأقل</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">تأكيد كلمة المرور</label>
                            <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="install.php?step=3" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-2"></i>
                                السابق
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                التالي
                            </button>
                        </div>
                    </form>

                <!-- Step 5: Additional Settings -->
                <?php elseif ($current_step == 5): ?>
                    <h3 class="mb-4">
                        <i class="fas fa-cogs me-2"></i>
                        إعدادات إضافية (اختيارية)
                    </h3>
                    
                    <form method="POST">
                        <div class="accordion" id="additionalSettings">
                            <!-- Email Settings -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#emailSettings">
                                        <i class="fas fa-envelope me-2"></i>
                                        إعدادات البريد الإلكتروني
                                    </button>
                                </h2>
                                <div id="emailSettings" class="accordion-collapse collapse" data-bs-parent="#additionalSettings">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">خادم SMTP</label>
                                                    <input type="text" name="mail_host" class="form-control" placeholder="smtp.gmail.com">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">منفذ SMTP</label>
                                                    <input type="number" name="mail_port" class="form-control" placeholder="587">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">اسم المستخدم</label>
                                                    <input type="email" name="mail_username" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">كلمة المرور</label>
                                                    <input type="password" name="mail_password" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">نوع التشفير</label>
                                            <select name="mail_encryption" class="form-select">
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                                <option value="">بدون تشفير</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Google Maps -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mapsSettings">
                                        <i class="fas fa-map me-2"></i>
                                        خرائط جوجل
                                    </button>
                                </h2>
                                <div id="mapsSettings" class="accordion-collapse collapse" data-bs-parent="#additionalSettings">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label class="form-label">Google Maps API Key</label>
                                            <input type="text" name="google_maps_key" class="form-control">
                                            <div class="form-text">مطلوب لعرض الخرائط وحساب المسافات</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Push Notifications -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pushSettings">
                                        <i class="fas fa-bell me-2"></i>
                                        الإشعارات الفورية
                                    </button>
                                </h2>
                                <div id="pushSettings" class="accordion-collapse collapse" data-bs-parent="#additionalSettings">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label class="form-label">FCM Server Key</label>
                                            <input type="text" name="fcm_server_key" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">FCM Sender ID</label>
                                            <input type="text" name="fcm_sender_id" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Gateway -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentSettings">
                                        <i class="fas fa-credit-card me-2"></i>
                                        بوابة الدفع (Paymob)
                                    </button>
                                </h2>
                                <div id="paymentSettings" class="accordion-collapse collapse" data-bs-parent="#additionalSettings">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label class="form-label">Paymob API Key</label>
                                            <input type="text" name="paymob_api_key" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Integration ID</label>
                                            <input type="text" name="paymob_integration_id" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="install.php?step=4" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-2"></i>
                                السابق
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                التالي
                            </button>
                        </div>
                    </form>

                <!-- Step 6: Final Installation -->
                <?php elseif ($current_step == 6): ?>
                    <h3 class="mb-4">
                        <i class="fas fa-rocket me-2"></i>
                        التثبيت النهائي
                    </h3>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        مراجعة الإعدادات قبل التثبيت النهائي
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>معلومات الموقع:</h5>
                            <ul class="list-unstyled">
                                <li><strong>اسم الموقع:</strong> <?= $_SESSION['site_config']['app_name'] ?></li>
                                <li><strong>الرابط:</strong> <?= $_SESSION['site_config']['app_url'] ?></li>
                                <li><strong>الدعم الفني:</strong> <?= $_SESSION['site_config']['support_phone'] ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>قاعدة البيانات:</h5>
                            <ul class="list-unstyled">
                                <li><strong>الخادم:</strong> <?= $_SESSION['db_config']['db_host'] ?>:<?= $_SESSION['db_config']['db_port'] ?></li>
                                <li><strong>قاعدة البيانات:</strong> <?= $_SESSION['db_config']['db_database'] ?></li>
                                <li><strong>المستخدم:</strong> <?= $_SESSION['db_config']['db_username'] ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>حساب المدير:</h5>
                        <ul class="list-unstyled">
                            <li><strong>الاسم:</strong> <?= $_SESSION['admin_config']['name'] ?></li>
                            <li><strong>البريد الإلكتروني:</strong> <?= $_SESSION['admin_config']['email'] ?></li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <div class="d-flex justify-content-between">
                            <a href="install.php?step=5" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-2"></i>
                                السابق
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-rocket me-2"></i>
                                بدء التثبيت
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.querySelector('input[name="password"]');
            const confirmation = document.querySelector('input[name="password_confirmation"]');
            
            if (password && confirmation) {
                function validatePassword() {
                    if (password.value !== confirmation.value) {
                        confirmation.setCustomValidity('كلمات المرور غير متطابقة');
                    } else {
                        confirmation.setCustomValidity('');
                    }
                }
                
                password.addEventListener('input', validatePassword);
                confirmation.addEventListener('input', validatePassword);
            }
        });
    </script>
</body>
</html>