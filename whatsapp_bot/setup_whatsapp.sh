#!/bin/bash

# Poshy Lifestyle - WhatsApp Bot Setup Script for Fedora
# This script installs and configures the FREE WhatsApp bot

echo "🌙 ═══════════════════════════════════════════════"
echo "    Poshy Lifestyle WhatsApp Bot Setup"
echo "    FREE Solution - No API costs!"
echo "═══════════════════════════════════════════════ 🌙"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "⚠️  Please run as root: sudo bash setup_whatsapp.sh"
    exit 1
fi

echo "📦 Step 1: Installing Node.js and npm..."
dnf install -y nodejs npm

echo ""
echo "✓ Node.js version: $(node --version)"
echo "✓ npm version: $(npm --version)"
echo ""

echo "📦 Step 2: Installing PM2 (Process Manager)..."
npm install -g pm2

echo ""
echo "📁 Step 3: Installing bot dependencies..."
cd /var/www/html/poshy_store/whatsapp_bot
npm install

echo ""
echo "📂 Step 4: Creating directories..."
mkdir -p /var/www/html/poshy_store/pending_sms
mkdir -p /var/www/html/poshy_store/pending_sms/errors
chmod 755 /var/www/html/poshy_store/pending_sms
chmod 755 /var/www/html/poshy_store/pending_sms/errors

# Set permissions for Apache/PHP to write files
chown -R apache:apache /var/www/html/poshy_store/pending_sms

echo ""
echo "🚀 Step 5: Starting WhatsApp bot with PM2..."
pm2 start bot.js --name "poshy-whatsapp"
pm2 save
pm2 startup systemd -u root --hp /root

echo ""
echo "✅ Setup Complete!"
echo ""
echo "═══════════════════════════════════════════════"
echo "  📱 NEXT STEPS - IMPORTANT!"
echo "═══════════════════════════════════════════════"
echo ""
echo "1️⃣  View the QR code to link WhatsApp:"
echo "   $ pm2 logs poshy-whatsapp"
echo ""
echo "2️⃣  Scan the QR code with WhatsApp:"
echo "   • Open WhatsApp on your phone"
echo "   • Go to: Settings > Linked Devices"
echo "   • Tap: Link a Device"
echo "   • Scan the QR code shown in the logs"
echo ""
echo "3️⃣  Check bot status:"
echo "   $ pm2 status"
echo "   $ pm2 logs poshy-whatsapp"
echo ""
echo "4️⃣  Monitor sent messages:"
echo "   $ tail -f /var/www/html/poshy_store/whatsapp_bot/bot.log"
echo ""
echo "═══════════════════════════════════════════════"
echo "  🔧 USEFUL PM2 COMMANDS"
echo "═══════════════════════════════════════════════"
echo ""
echo "• pm2 restart poshy-whatsapp  - Restart bot"
echo "• pm2 stop poshy-whatsapp     - Stop bot"
echo "• pm2 delete poshy-whatsapp   - Remove bot"
echo "• pm2 monit                    - Real-time monitor"
echo ""
echo "🌙 Ramadan Kareem from Poshy Lifestyle! 💜"
echo ""
