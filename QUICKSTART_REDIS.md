# Quick Start Guide - Redis OTP

## 🚀 Langkah Cepat

### 1. Jalankan Redis
```bash
./start-redis.sh
```

### 2. Update .env (jika belum)
```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Clear Cache Laravel
```bash
php artisan config:clear && php artisan cache:clear
```

### 4. Test Koneksi
```bash
php artisan tinker
```
Di tinker:
```php
Redis::set('test', 'OK');
Redis::get('test');  // output: "OK"
exit
```

### 5. Jalankan App
```bash
php artisan serve
```

---

## 📝 Testing OTP

### Via Browser:
1. Buka: `http://127.0.0.1:8000/otp/login`
2. Masukkan email terdaftar
3. Lihat OTP di response/console
4. Input OTP untuk login

### Via Postman:
```
POST http://127.0.0.1:8000/otp/generate
Headers: Accept: application/json
Body: {"email":"user@example.com"}
```

---

## 🔍 Monitor Redis

```bash
# Masuk ke Redis CLI
docker exec -it otp-laravel-redis redis-cli

# Lihat semua OTP keys
KEYS otp:user:*

# Lihat specific OTP
GET otp:user:1

# Check TTL (sisa waktu)
TTL otp:user:1
```

---

## ⚠️ Troubleshooting

### Redis tidak bisa connect?
```bash
# Check Redis status
docker-compose ps

# Check logs
docker-compose logs redis

# Restart Redis
docker-compose restart redis
```

### PHP Redis extension not found?
```bash
# Install phpredis
sudo apt-get install php-redis

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

## 🛑 Stop Redis
```bash
./stop-redis.sh
```

---

**Full Documentation:** [REDIS_OTP_SETUP.md](REDIS_OTP_SETUP.md)
