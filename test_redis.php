<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "Testing Redis Connection...\n";

try {
    $result = Redis::ping();
    echo "✅ Redis Connected! Ping result: " . $result . "\n";
    
    // Test set and get
    Redis::set('test_key', 'Hello from Redis!');
    $value = Redis::get('test_key');
    echo "✅ Redis Set/Get works: " . $value . "\n";
    
    Redis::del('test_key');
    echo "✅ All tests passed!\n";
} catch (Exception $e) {
    echo "❌ Redis Error: " . $e->getMessage() . "\n";
}
