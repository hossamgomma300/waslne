# Waslne - وصلني | Egyptian Ride-Sharing Platform

![Waslne Logo](public/images/logo.png)

Waslne is a comprehensive ride-sharing platform built specifically for the Egyptian market, similar to inDrive. It allows passengers to set their own prices and drivers to bid on trips, creating a competitive marketplace for transportation services.

## 🚀 Features

### Core Features
- **Passenger App**: Request rides, set your price, choose from driver offers
- **Driver App**: View nearby trips, make offers, manage earnings
- **Admin Panel**: Complete management system with analytics and reports
- **Real-time Tracking**: GPS tracking for live location updates
- **Multi-payment Support**: Cash, Cards, Fawry, Vodafone Cash, Orange Money
- **Rating System**: Comprehensive rating system for both passengers and drivers
- **Wallet System**: Internal wallet with top-up and transfer capabilities

### Egyptian Market Specific
- **Arabic Language Support**: Full RTL support with Egyptian dialect
- **Local Payment Gateways**: Paymob, Fawry, Vodafone Cash, Orange Money
- **Egyptian Phone Numbers**: OTP verification with Egyptian mobile formats
- **Local Currency**: EGP (Egyptian Pound) support
- **Cairo Timezone**: Configured for Africa/Cairo timezone

## 🛠 Technology Stack

- **Backend**: PHP 8.x with Laravel 10+
- **Database**: MySQL/MariaDB
- **Authentication**: JWT (JSON Web Tokens)
- **Real-time**: Laravel Echo + Pusher/WebSockets
- **Queue System**: Redis
- **Cache**: Redis
- **File Storage**: Local/AWS S3/DigitalOcean Spaces
- **Maps**: Google Maps API / Mapbox
- **Push Notifications**: Firebase Cloud Messaging (FCM)
- **SMS**: Nexmo/Twilio + Local Egyptian SMS providers

## 📋 Requirements

- PHP >= 8.1
- Composer
- Node.js >= 16.x
- MySQL >= 8.0 or MariaDB >= 10.4
- Redis >= 6.0
- Web server (Apache/Nginx)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/waslne.git
cd waslne
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### 4. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE waslne CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed
```

### 5. Storage Setup
```bash
# Create storage link
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### 6. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

## ⚙️ Configuration

### Environment Variables

Edit your `.env` file with the following configurations:

#### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=waslne
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Payment Gateways
```env
# Paymob
PAYMOB_API_KEY=your_paymob_api_key
PAYMOB_INTEGRATION_ID=your_integration_id
PAYMOB_IFRAME_ID=your_iframe_id

# Fawry
FAWRY_MERCHANT_CODE=your_merchant_code
FAWRY_SECURITY_KEY=your_security_key

# Vodafone Cash
VODAFONE_MERCHANT_ID=your_merchant_id
VODAFONE_API_KEY=your_api_key
```

#### Maps & Location
```env
GOOGLE_MAPS_API_KEY=your_google_maps_key
GOOGLE_PLACES_API_KEY=your_places_key
GOOGLE_DIRECTIONS_API_KEY=your_directions_key
```

#### SMS Services
```env
NEXMO_KEY=your_nexmo_key
NEXMO_SECRET=your_nexmo_secret
```

#### Push Notifications
```env
FCM_SERVER_KEY=your_fcm_server_key
FCM_SENDER_ID=your_sender_id
```

## 🏃‍♂️ Running the Application

### Development
```bash
# Start Laravel development server
php artisan serve

# Start queue worker
php artisan queue:work

# Start WebSocket server (if using Laravel WebSockets)
php artisan websockets:serve

# Watch for asset changes
npm run dev
```

### Production
```bash
# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start queue workers with supervisor
php artisan queue:work --daemon

# Build production assets
npm run build
```

## 📱 API Documentation

### Authentication Endpoints
```
POST /api/auth/register          - Register new user
POST /api/auth/login             - User login
POST /api/auth/request-otp       - Request OTP for phone verification
POST /api/auth/verify-otp        - Verify OTP code
GET  /api/auth/profile           - Get user profile
PUT  /api/auth/profile           - Update user profile
POST /api/auth/logout            - Logout user
POST /api/auth/refresh           - Refresh JWT token
```

### Trip Endpoints
```
POST /api/trips                  - Create new trip request
GET  /api/trips/nearby           - Get nearby trips (for drivers)
GET  /api/trips/my-trips         - Get user's trips
GET  /api/trips/{id}             - Get trip details
POST /api/trips/{id}/cancel      - Cancel trip
```

### Offer Endpoints
```
POST /api/offers/trips/{tripId}  - Create offer for trip
POST /api/offers/{id}/accept     - Accept offer
POST /api/offers/{id}/reject     - Reject offer
POST /api/offers/{id}/withdraw   - Withdraw offer
GET  /api/offers/trips/{tripId}  - Get trip offers
GET  /api/offers/my-offers       - Get driver's offers
```

### Driver Endpoints
```
POST /api/driver/register        - Register as driver
GET  /api/driver/profile         - Get driver profile
POST /api/driver/location        - Update driver location
POST /api/driver/toggle-online   - Toggle online status
POST /api/driver/trips/{id}/start - Start trip
POST /api/driver/trips/{id}/arrived - Mark as arrived
POST /api/driver/trips/{id}/complete - Complete trip
GET  /api/driver/earnings        - Get driver earnings
```

### Payment Endpoints
```
POST /api/payments/trips/{id}/initiate - Initiate payment
GET  /api/payments/{id}/status   - Get payment status
```

### Wallet Endpoints
```
GET  /api/wallet                 - Get wallet balance
POST /api/wallet/add-money       - Add money to wallet
POST /api/wallet/transfer        - Transfer money
GET  /api/wallet/transactions    - Get wallet transactions
```

## 🔧 Admin Panel

Access the admin panel at `/admin` with admin credentials.

### Admin Features
- **Dashboard**: Overview statistics and charts
- **User Management**: Manage passengers and drivers
- **Trip Management**: Monitor and manage all trips
- **Payment Management**: Handle payments and refunds
- **Driver Approval**: Approve/reject driver applications
- **Financial Reports**: Revenue and commission reports
- **System Settings**: Configure platform settings

### Default Admin Credentials
```
Email: admin@waslne.com
Password: admin123
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 📊 Monitoring & Logging

### Queue Monitoring
```bash
# Monitor queue status
php artisan queue:monitor

# Restart queue workers
php artisan queue:restart
```

### Logs
- Application logs: `storage/logs/laravel.log`
- Payment logs: `storage/logs/payments.log`
- SMS logs: `storage/logs/sms.log`

## 🚀 Deployment

### Server Requirements
- PHP 8.1+ with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- MySQL 8.0+ or MariaDB 10.4+
- Redis 6.0+
- Nginx or Apache
- SSL Certificate (required for production)

### Deployment Steps
1. Clone repository to server
2. Install dependencies: `composer install --no-dev --optimize-autoloader`
3. Configure environment variables
4. Run migrations: `php artisan migrate --force`
5. Cache configuration: `php artisan config:cache`
6. Set up queue workers with Supervisor
7. Configure web server (Nginx/Apache)
8. Set up SSL certificate
9. Configure cron jobs for scheduled tasks

### Cron Jobs
Add to crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔒 Security

- JWT authentication with refresh tokens
- HTTPS enforcement in production
- CSRF protection
- XSS protection
- SQL injection prevention with Eloquent ORM
- Rate limiting on API endpoints
- Input validation and sanitization
- Secure file upload handling

## 🌍 Localization

The application supports:
- Arabic (Egypt) - Primary language
- English - Secondary language

Translation files are located in `resources/lang/`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

- **Email**: support@waslne.com
- **Phone**: +201234567890
- **Documentation**: [docs.waslne.com](https://docs.waslne.com)
- **Issues**: [GitHub Issues](https://github.com/your-username/waslne/issues)

## 🙏 Acknowledgments

- Laravel Framework
- Egyptian payment gateway providers
- Google Maps API
- Firebase Cloud Messaging
- All open-source contributors

---

**Made with ❤️ for Egypt** 🇪🇬