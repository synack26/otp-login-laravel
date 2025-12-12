# OTP Login with Redis Storage

Sistem OTP telah diubah untuk menggunakan **Redis** sebagai storage, menggantikan database MySQL/SQLite.

## 🚀 Setup Instructions

### 1. Jalankan Redis dengan Docker Compose

```bash
# Start Redis container
docker-compose up -d

# Verify Redis is running
docker-compose ps
```

### 2. Install Redis PHP Extension (jika belum ada)

**Untuk Ubuntu/Debian:**
```bash
sudo apt-get install php-redis
```

**Untuk Mac (Homebrew):**
```bash
brew install php-redis
```

**Atau install via PECL:**
```bash
pecl install redis
```

### 3. Update file .env

Pastikan konfigurasi Redis di `.env`:

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Jika menggunakan Redis dari Docker, pastikan host sesuai:
```env
REDIS_HOST=127.0.0.1  # atau 'redis' jika Laravel juga di Docker
```

### 4. Clear Cache Laravel

```bash
php artisan config:clear
php artisan cache:clear
```

### 5. Test Koneksi Redis

```bash
php artisan tinker
```

Di tinker, jalankan:
```php
Redis::set('test', 'success');
Redis::get('test'); // should return 'success'
```

## 📋 Perubahan yang Dilakukan

### Files yang Dimodifikasi:

1. **app/Services/OtpService.php** (NEW)
   - Service class untuk manage OTP di Redis
   - Methods: `generateOtp()`, `verifyOtp()`, `expireOtp()`, `isExpired()`

2. **app/Http/Controllers/AuthOtpController.php**
   - Menggunakan `OtpService` instead of `VerificationCode` model
   - OTP disimpan di Redis dengan TTL 10 menit

3. **app/Http/Controllers/Auth/LoginController.php**
   - Update untuk pakai `OtpService`

4. **app/Http/Controllers/Auth/RegisterController.php**
   - Update untuk pakai `OtpService`

5. **docker-compose.yml** (NEW)
   - Redis container configuration

## 🔑 Redis Key Structure

OTP disimpan di Redis dengan format:

```
Key: otp:user:{user_id}
Value: JSON object
{
  "otp": "123456",
  "user_id": 1,
  "expired_at": "2025-12-12 15:30:00",
  "created_at": "2025-12-12 15:20:00"
}
TTL: 600 seconds (10 minutes)
```

## 🧪 Testing OTP

### Via Web (Browser):
1. Akses `/otp/login`
2. Masukkan email
3. Cek response untuk mendapat OTP
4. Masukkan OTP untuk login

### Via API (JSON):
```bash
# Generate OTP
curl -X POST http://your-domain/otp/generate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user@example.com"}'

# Response:
# {
#   "success": "Enter your OTP to login",
#   "otp": 123456,
#   "user_id": 1,
#   "expires_at": "2025-12-12 15:30:00"
# }

# Login with OTP
curl -X POST http://your-domain/otp/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"user_id":1, "otp":"123456"}'
```

## 🔍 Monitor Redis

```bash
# Connect to Redis CLI in Docker
docker exec -it otp-laravel-redis redis-cli

# List all OTP keys
KEYS otp:user:*

# Get specific OTP
GET otp:user:1

# Check TTL
TTL otp:user:1

# Monitor all commands
MONITOR
```

## ⚠️ Troubleshooting

### Redis Connection Error
```
Error: Connection refused [tcp://127.0.0.1:6379]
```
**Solusi:**
- Pastikan Redis container running: `docker-compose ps`
- Check Redis logs: `docker-compose logs redis`
- Verify port tidak dipakai service lain: `lsof -i :6379`

### PHP Redis Extension Not Found
```
Error: Class 'Redis' not found
```
**Solusi:**
- Install PHP Redis extension (lihat instruksi di atas)
- Restart PHP-FPM/Apache: `sudo systemctl restart php8.2-fpm`

### OTP Not Stored
**Solusi:**
- Check Redis config di `.env`
- Clear config: `php artisan config:clear`
- Test koneksi via tinker

## 📊 Keuntungan Redis vs Database

| Aspek | Database | Redis |
|-------|----------|-------|
| **Speed** | ~100ms | ~1ms |
| **TTL** | Manual cleanup | Automatic expiration |
| **Scalability** | Limited | High |
| **Load** | Heavy disk I/O | In-memory |
| **Cleanup** | Cronjob needed | Auto-delete |

## 🔄 Rollback ke Database (Optional)

Jika ingin kembali ke database storage:
1. Revert changes di controllers
2. Gunakan kembali `VerificationCode` model
3. Comment out Redis dependency

## 📚 Reference

- [Laravel Redis Documentation](https://laravel.com/docs/10.x/redis)
- [Redis Commands](https://redis.io/commands)
- [phpredis GitHub](https://github.com/phpredis/phpredis)
