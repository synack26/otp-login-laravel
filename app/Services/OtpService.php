<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class OtpService
{
    /**
     * OTP expiration time in seconds (10 minutes)
     */
    private const OTP_EXPIRATION = 600;

    /**
     * Generate and store OTP in Redis
     * 
     * @param int $userId
     * @return array ['otp' => string, 'expired_at' => Carbon]
     */
    public function generateOtp(int $userId): array
    {
        // Expire all previous OTPs for this user
        $this->expireOtp($userId);

        // Generate 6-digit OTP
        $otp = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiredAt = Carbon::now()->addMinutes(10);

        // Store OTP in Redis with TTL
        $key = $this->getRedisKey($userId);
        Redis::setex($key, self::OTP_EXPIRATION, json_encode([
            'otp' => $otp,
            'user_id' => $userId,
            'expired_at' => $expiredAt->toDateTimeString(),
            'created_at' => Carbon::now()->toDateTimeString()
        ]));

        return [
            'otp' => $otp,
            'user_id' => $userId,
            'expired_at' => $expiredAt
        ];
    }

    /**
     * Verify OTP
     * 
     * @param int $userId
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(int $userId, string $otp): bool
    {
        $key = $this->getRedisKey($userId);
        $data = Redis::get($key);

        if (!$data) {
            return false;
        }

        $otpData = json_decode($data, true);
        
        // Check if OTP matches
        if ($otpData['otp'] !== $otp) {
            return false;
        }

        // Check if OTP is expired
        $expiredAt = Carbon::parse($otpData['expired_at']);
        if (Carbon::now()->isAfter($expiredAt)) {
            $this->expireOtp($userId);
            return false;
        }

        return true;
    }

    /**
     * Get OTP data from Redis
     * 
     * @param int $userId
     * @return array|null
     */
    public function getOtp(int $userId): ?array
    {
        $key = $this->getRedisKey($userId);
        $data = Redis::get($key);

        if (!$data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Check if OTP is expired
     * 
     * @param int $userId
     * @return bool
     */
    public function isExpired(int $userId): bool
    {
        $otpData = $this->getOtp($userId);

        if (!$otpData) {
            return true;
        }

        $expiredAt = Carbon::parse($otpData['expired_at']);
        return Carbon::now()->isAfter($expiredAt);
    }

    /**
     * Expire/delete OTP from Redis
     * 
     * @param int $userId
     * @return void
     */
    public function expireOtp(int $userId): void
    {
        $key = $this->getRedisKey($userId);
        Redis::del($key);
    }

    /**
     * Get Redis key for user OTP
     * 
     * @param int $userId
     * @return string
     */
    private function getRedisKey(int $userId): string
    {
        return "otp:user:{$userId}";
    }

    /**
     * Get remaining TTL for OTP
     * 
     * @param int $userId
     * @return int seconds remaining, -1 if expired, -2 if not exists
     */
    public function getRemainingTime(int $userId): int
    {
        $key = $this->getRedisKey($userId);
        return Redis::ttl($key);
    }
}
