#!/bin/bash

# Quick test script for WhatsApp bot
# Creates a test message to verify the system is working

PHONE_NUMBER="$1"

if [ -z "$PHONE_NUMBER" ]; then
    echo "Usage: bash test_whatsapp.sh +962791234567"
    echo ""
    echo "Example: bash test_whatsapp.sh +962791234567"
    exit 1
fi

echo "🧪 Creating test WhatsApp message..."
echo "   Phone: $PHONE_NUMBER"
echo ""

cat > /var/www/html/poshy_store/pending_sms/test_$(date +%s).json << EOF
{
  "phone": "$PHONE_NUMBER",
  "message": "🧪 Test Message from Poshy Lifestyle\n\n✅ If you received this, your WhatsApp bot is working perfectly!\n\n🌙 Ramadan Kareem! 💜\n\nFrom: Poshy Lifestyle Bot",
  "timestamp": "$(date '+%Y-%m-%d %H:%M:%S')",
  "type": "test"
}
EOF

echo "✅ Test message queued!"
echo ""
echo "📱 Check your WhatsApp in a few seconds..."
echo ""
echo "💡 Monitor the bot:"
echo "   pm2 logs poshy-whatsapp"
echo ""
