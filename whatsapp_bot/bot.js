/**
 * Poshy Lifestyle - WhatsApp Order Confirmation Bot
 * FREE Solution using whatsapp-web.js (No API costs!)
 * 
 * This bot monitors for new order JSON files and sends WhatsApp messages automatically
 */

const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const chokidar = require('chokidar');
const fs = require('fs');
const path = require('path');

function normalizePhoneNumber(value) {
    let phone = String(value || '').replace(/[^0-9]/g, '');

    if (!phone) {
        return '';
    }

    // Support international format written as 00XXXXXXXX.
    if (phone.startsWith('00')) {
        phone = phone.substring(2);
    }

    // Remove a single local leading zero: 07XXXXXXXX -> 7XXXXXXXX
    if (phone.startsWith('0')) {
        phone = phone.substring(1);
    }

    // Force Jordan country code prefix when missing.
    if (!phone.startsWith('962')) {
        phone = '962' + phone;
    }

    return phone;
}

// Configuration
const BASE_DIR = path.resolve(__dirname, '..');
const PENDING_SMS_DIR = process.env.WHATSAPP_PENDING_DIR || path.join(BASE_DIR, 'pending_sms');
const LOG_FILE = process.env.WHATSAPP_LOG_FILE || path.join(__dirname, 'bot.log');
const EXPECTED_SENDER_NUMBER = normalizePhoneNumber(process.env.WHATSAPP_SENDER_NUMBER || '962770058416');
let senderVerified = false;

// Ensure pending_sms directory exists
if (!fs.existsSync(PENDING_SMS_DIR)) {
    fs.mkdirSync(PENDING_SMS_DIR, { recursive: true });
    console.log(`✓ Created directory: ${PENDING_SMS_DIR}`);
}

// Logging function
function log(message) {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] ${message}\n`;
    console.log(logMessage.trim());
    fs.appendFileSync(LOG_FILE, logMessage);
}

// Initialize WhatsApp Client with LocalAuth for persistence
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: path.join(__dirname, '.wwebjs_auth')
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
        ]
    }
});

// QR Code Generation (first time only)
client.on('qr', (qr) => {
    console.log('\n========================================');
    console.log('🌙 POSHY LIFESTYLE - WhatsApp Bot');
    console.log('========================================\n');
    console.log('📱 Scan this QR code with WhatsApp:');
    console.log('   1. Open WhatsApp on your phone');
    console.log('   2. Go to Settings > Linked Devices');
    console.log('   3. Tap "Link a Device"');
    console.log('   4. Scan the QR code below:\n');
    console.log('   5. Use business number: +962 7 7005 8416\n');
    
    qrcode.generate(qr, { small: true });
    
    console.log('\n⏳ Waiting for QR scan...\n');
    log('QR Code generated. Waiting for scan...');
});

// Client Ready
client.on('ready', () => {
    const connectedSenderRaw = (client.info && client.info.wid && client.info.wid.user) ? client.info.wid.user : '';
    const connectedSender = normalizePhoneNumber(connectedSenderRaw);

    senderVerified = connectedSender !== '' && connectedSender === EXPECTED_SENDER_NUMBER;

    if (!senderVerified) {
        console.error('\n❌ Connected WhatsApp account does not match required business number.');
        console.error(`Expected: ${EXPECTED_SENDER_NUMBER}`);
        console.error(`Connected: ${connectedSender || 'unknown'}\n`);
        log(`Sender mismatch. expected=${EXPECTED_SENDER_NUMBER} connected=${connectedSender || 'unknown'}`);
        log('Message sending is paused until the correct WhatsApp account is linked.');
    } else {
        log(`Sender verified: ${connectedSender}`);
    }

    console.log('\n✅ WhatsApp Bot is READY and connected!');
    console.log(senderVerified
        ? '🚀 Monitoring for order confirmations...'
        : '⚠️ Monitoring queue only (sending paused: wrong sender account).');
    console.log(`📁 Watching directory: ${PENDING_SMS_DIR}\n`);
    log('WhatsApp client is ready and authenticated');
    
    // Start watching for JSON files
    startFileWatcher();
});

// Authentication
client.on('authenticated', () => {
    console.log('✓ Authentication successful!');
    log('WhatsApp authenticated successfully');
});

// Authentication failure
client.on('auth_failure', (msg) => {
    console.error('❌ Authentication failed:', msg);
    log(`Authentication failed: ${msg}`);
});

// Disconnection
client.on('disconnected', (reason) => {
    console.log('⚠️ WhatsApp disconnected:', reason);
    log(`Disconnected: ${reason}`);
});

// File Watcher
function startFileWatcher() {
    const watcher = chokidar.watch(`${PENDING_SMS_DIR}/*.json`, {
        persistent: true,
        ignoreInitial: false,
        awaitWriteFinish: {
            stabilityThreshold: 500,
            pollInterval: 100
        }
    });

    watcher.on('add', async (filePath) => {
        log(`New order file detected: ${path.basename(filePath)}`);
        await processOrderFile(filePath);
    });

    watcher.on('error', (error) => {
        log(`Watcher error: ${error}`);
    });

    log('File watcher started successfully');
}

// Process Order File and Send WhatsApp
async function processOrderFile(filePath) {
    try {
        if (!senderVerified) {
            log(`Skipped ${path.basename(filePath)}: sender account is not +962770058416`);
            return;
        }

        // Read JSON file
        const fileContent = fs.readFileSync(filePath, 'utf8');
        const orderData = JSON.parse(fileContent);

        // Validate data
        if (!orderData.phone || !orderData.message) {
            log(`Invalid order file: ${filePath} - Missing phone or message`);
            fs.unlinkSync(filePath); // Delete invalid file
            return;
        }

        // Format phone number for WhatsApp
        let phoneNumber = normalizePhoneNumber(orderData.phone);
        if (!phoneNumber || phoneNumber.length < 11) {
            throw new Error(`Invalid recipient phone number: ${orderData.phone}`);
        }

        const whatsappNumber = phoneNumber + '@c.us';

        // Send WhatsApp message
        console.log(`\n📤 Sending message to: ${phoneNumber}`);
        log(`Attempting to send message to ${phoneNumber}`);

        await client.sendMessage(whatsappNumber, orderData.message);

        console.log(`✅ Message sent successfully to ${phoneNumber}`);
        log(`Message sent successfully to ${phoneNumber}`);
        
        // Log order details for debugging
        if (orderData.order_id) {
            log(`Order #${orderData.order_id} confirmation sent`);
        }

        // Delete the processed file
        fs.unlinkSync(filePath);
        log(`File deleted: ${path.basename(filePath)}`);

    } catch (error) {
        console.error(`❌ Error processing ${path.basename(filePath)}:`, error.message);
        log(`Error processing file: ${error.message}`);
        
        // Move failed file to error directory
        const errorDir = path.join(PENDING_SMS_DIR, 'errors');
        if (!fs.existsSync(errorDir)) {
            fs.mkdirSync(errorDir, { recursive: true });
        }
        
        const errorFile = path.join(errorDir, `error_${Date.now()}_${path.basename(filePath)}`);
        fs.renameSync(filePath, errorFile);
        log(`Moved failed file to: ${errorFile}`);
    }
}

// Handle process termination
process.on('SIGINT', async () => {
    console.log('\n\n🛑 Shutting down WhatsApp bot...');
    log('Bot shutting down (SIGINT)');
    await client.destroy();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    log('Bot shutting down (SIGTERM)');
    await client.destroy();
    process.exit(0);
});

// Start the client
console.log('🚀 Starting Poshy Lifestyle WhatsApp Bot...\n');
console.log(`📞 Required sender number: ${EXPECTED_SENDER_NUMBER}`);
console.log(`📁 Pending directory: ${PENDING_SMS_DIR}`);
log('Bot starting...');
client.initialize();
