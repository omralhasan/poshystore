/**
 * Poshy WhatsApp queue bot.
 *
 * Reads JSON payloads from pending_sms and sends confirmations via whatsapp-web.js.
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

    if (phone.startsWith('00')) {
        phone = phone.substring(2);
    }

    // Remove one local leading zero: 07XXXXXXXX -> 7XXXXXXXX
    if (phone.startsWith('0')) {
        phone = phone.substring(1);
    }

    // Force Jordan code prefix when missing.
    if (!phone.startsWith('962')) {
        phone = `962${phone}`;
    }

    return phone;
}

function isDirReadable(dirPath) {
    try {
        fs.accessSync(dirPath, fs.constants.R_OK);
        return true;
    } catch (_error) {
        return false;
    }
}

function ensureDir(dirPath) {
    if (!fs.existsSync(dirPath)) {
        fs.mkdirSync(dirPath, { recursive: true });
    }
}

function resolvePendingSmsDir(baseDir) {
    const fromEnv = String(process.env.WHATSAPP_PENDING_DIR || '').trim();
    if (fromEnv !== '') {
        return fromEnv;
    }

    const candidates = [
        '/var/www/html/poshy_store/pending_sms',
        '/var/www/html/pending_sms',
        path.join(baseDir, 'pending_sms')
    ];

    for (const candidate of candidates) {
        if (isDirReadable(candidate)) {
            return candidate;
        }
    }

    return path.join(baseDir, 'pending_sms');
}

function resolveAuthDataPath() {
    const fromEnv = String(process.env.WHATSAPP_AUTH_DIR || '').trim();
    if (fromEnv !== '') {
        return fromEnv;
    }

    return path.join(__dirname, '.wwebjs_auth_stable');
}

const BASE_DIR = path.resolve(__dirname, '..');
const PENDING_SMS_DIR = resolvePendingSmsDir(BASE_DIR);
const LOG_FILE = process.env.WHATSAPP_LOG_FILE || path.join(__dirname, 'bot.log');
const AUTH_DATA_PATH = resolveAuthDataPath();
const EXPECTED_SENDER_NUMBER = normalizePhoneNumber(process.env.WHATSAPP_SENDER_NUMBER || '962770058416');

let senderVerified = false;

ensureDir(PENDING_SMS_DIR);

function log(message) {
    const timestamp = new Date().toISOString();
    const line = `[${timestamp}] ${message}`;
    console.log(line);
    fs.appendFileSync(LOG_FILE, `${line}\n`);
}

const client = new Client({
    authStrategy: new LocalAuth({ dataPath: AUTH_DATA_PATH }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--disable-gpu',
            '--no-first-run',
            '--no-zygote'
        ]
    }
});

client.on('qr', (qr) => {
    console.log('Waiting for QR scan...');
    qrcode.generate(qr, { small: true });
    log('QR code generated');
});

client.on('authenticated', () => {
    log('WhatsApp authenticated');
});

client.on('auth_failure', (msg) => {
    log(`Authentication failed: ${msg}`);
});

client.on('disconnected', (reason) => {
    log(`Disconnected: ${reason}`);
});

client.on('ready', () => {
    const connectedRaw = client.info && client.info.wid ? client.info.wid.user : '';
    const connectedSender = normalizePhoneNumber(connectedRaw);

    senderVerified = connectedSender !== '' && connectedSender === EXPECTED_SENDER_NUMBER;

    if (senderVerified) {
        log(`Client ready. Sender verified: ${connectedSender}`);
    } else {
        log(`Client ready but sender mismatch. expected=${EXPECTED_SENDER_NUMBER} connected=${connectedSender || 'unknown'}`);
    }

    startFileWatcher();
});

function startFileWatcher() {
    const watcher = chokidar.watch(`${PENDING_SMS_DIR}/*.json`, {
        persistent: true,
        ignoreInitial: false,
        awaitWriteFinish: {
            stabilityThreshold: 700,
            pollInterval: 100
        }
    });

    watcher.on('add', async (filePath) => {
        log(`Detected queue file: ${path.basename(filePath)}`);
        await processOrderFile(filePath);
    });

    watcher.on('error', (error) => {
        log(`Watcher error: ${error.message}`);
    });

    log(`Watching queue directory: ${PENDING_SMS_DIR}`);
}

async function processOrderFile(filePath) {
    try {
        if (!senderVerified) {
            log(`Skipped ${path.basename(filePath)} because sender is not verified`);
            return;
        }

        const payloadRaw = fs.readFileSync(filePath, 'utf8');
        const orderData = JSON.parse(payloadRaw);

        if (!orderData.phone || !orderData.message) {
            throw new Error('Queue file missing required phone or message');
        }

        const normalized = normalizePhoneNumber(orderData.phone);
        if (!normalized || normalized.length < 11) {
            throw new Error(`Invalid recipient phone: ${orderData.phone}`);
        }

        const jid = `${normalized}@c.us`;
        log(`Sending message to ${normalized}`);
        await client.sendMessage(jid, String(orderData.message));
        log(`Message sent to ${normalized}`);

        fs.unlinkSync(filePath);
        log(`Queue file removed: ${path.basename(filePath)}`);
    } catch (error) {
        const errorsDir = path.join(PENDING_SMS_DIR, 'errors');
        ensureDir(errorsDir);

        const failedName = `error_${Date.now()}_${path.basename(filePath)}`;
        const failedPath = path.join(errorsDir, failedName);

        try {
            if (fs.existsSync(filePath)) {
                fs.renameSync(filePath, failedPath);
            }
        } catch (_moveError) {
            // Keep original error as primary signal.
        }

        log(`Failed processing ${path.basename(filePath)}: ${error.message}`);
    }
}

process.on('SIGINT', async () => {
    log('Shutdown requested (SIGINT)');
    await client.destroy();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    log('Shutdown requested (SIGTERM)');
    await client.destroy();
    process.exit(0);
});

log('Starting WhatsApp bot');
log(`Auth data path: ${AUTH_DATA_PATH}`);
log(`Expected sender: ${EXPECTED_SENDER_NUMBER}`);
log(`Queue path: ${PENDING_SMS_DIR}`);
client.initialize();
