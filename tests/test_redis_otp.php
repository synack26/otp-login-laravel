<?php

/**
 * Test Redis OTP Service
 * 
 * Run this script via:
 * php artisan tinker < tests/test_redis_otp.php
 * 
 * Or manually in tinker:
 * php artisan tinker
 * > include 'tests/test_redis_otp.php';
 */

use App\Services\OtpService;
use Illuminate\Support\Facades\Redis;

echo "🧪 Testing Redis OTP Service\n";
echo "================================\n\n";

// Initialize OtpService
$otpService = new OtpService();

// Test 1: Redis Connection
echo "1️⃣  Testing Redis Connection...\n";
try {
    Redis::set('test_connection', 'success');
    $result = Redis::get('test_connection');
    if ($result === 'success') {
        echo "   ✅ Redis connected successfully\n";
        Redis::del('test_connection');
    }
} catch (Exception $e) {
    echo "   ❌ Redis connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Generate OTP
echo "2️⃣  Testing OTP Generation...\n";
$userId = 999; // Test user ID
$otpData = $otpService->generateOtp($userId);
echo "   ✅ OTP Generated: " . $otpData['otp'] . "\n";
echo "   📅 Expires At: " . $otpData['expired_at'] . "\n";
echo "   👤 User ID: " . $otpData['user_id'] . "\n";

echo "\n";

// Test 3: Retrieve OTP
echo "3️⃣  Testing OTP Retrieval...\n";
$retrievedOtp = $otpService->getOtp($userId);
if ($retrievedOtp && $retrievedOtp['otp'] === $otpData['otp']) {
    echo "   ✅ OTP retrieved successfully\n";
    echo "   🔑 OTP: " . $retrievedOtp['otp'] . "\n";
} else {
    echo "   ❌ Failed to retrieve OTP\n";
}

echo "\n";

// Test 4: Verify OTP (correct)
echo "4️⃣  Testing OTP Verification (correct)...\n";
$isValid = $otpService->verifyOtp($userId, $otpData['otp']);
if ($isValid) {
    echo "   ✅ OTP verified successfully\n";
} else {
    echo "   ❌ OTP verification failed\n";
}

echo "\n";

// Test 5: Verify OTP (incorrect)
echo "5️⃣  Testing OTP Verification (incorrect)...\n";
$isValid = $otpService->verifyOtp($userId, '000000');
if (!$isValid) {
    echo "   ✅ Incorrect OTP rejected correctly\n";
} else {
    echo "   ❌ Incorrect OTP accepted (ERROR!)\n";
}

echo "\n";

// Test 6: TTL Check
echo "6️⃣  Testing TTL (Time To Live)...\n";
$ttl = $otpService->getRemainingTime($userId);
if ($ttl > 0) {
    echo "   ✅ TTL: " . $ttl . " seconds remaining\n";
} else {
    echo "   ❌ OTP expired or not found\n";
}

echo "\n";

// Test 7: Expire OTP
echo "7️⃣  Testing OTP Expiration...\n";
$otpService->expireOtp($userId);
$expiredOtp = $otpService->getOtp($userId);
if (!$expiredOtp) {
    echo "   ✅ OTP expired successfully\n";
} else {
    echo "   ❌ OTP still exists after expiration\n";
}

echo "\n";

// Test 8: Verify expired OTP
echo "8️⃣  Testing Verification After Expiration...\n";
$isValid = $otpService->verifyOtp($userId, $otpData['otp']);
if (!$isValid) {
    echo "   ✅ Expired OTP rejected correctly\n";
} else {
    echo "   ❌ Expired OTP accepted (ERROR!)\n";
}

echo "\n";
echo "================================\n";
echo "✅ All tests completed!\n";
