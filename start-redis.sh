#!/bin/bash

echo "🚀 Starting Redis for OTP Laravel..."
docker compose up -d

echo ""
echo "✅ Checking Redis status..."
sleep 2
docker compose ps

echo ""
echo "🔍 Testing Redis connection..."
docker exec otp-laravel-redis redis-cli ping

echo ""
echo "📊 Redis is ready!"
echo "   - Host: 127.0.0.1"
echo "   - Port: 6379"
echo ""
echo "To monitor Redis:"
echo "   docker exec -it otp-laravel-redis redis-cli"
echo ""
echo "To view logs:"
echo "   docker compose logs -f redis"
echo ""
echo "To stop Redis:"
echo "   docker compose down"
