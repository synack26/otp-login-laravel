#!/bin/bash

echo "🛑 Stopping Redis..."
docker-compose down

echo ""
echo "✅ Redis stopped successfully!"
echo ""
echo "To start again:"
echo "   ./start-redis.sh"
echo "   or"
echo "   docker-compose up -d"
