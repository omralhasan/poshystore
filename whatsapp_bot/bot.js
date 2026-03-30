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

function ensureDir(dirPath) {
    if (!fs.existsSync(dirPath)) {
        fs.mkdirSync(dirPath, { recursive: true });
    }
}
const PENDING_SMS_DIR = '/var/www/html/poshy_store/pending_sms';
const ERROR_SMS_DIR = path.join(PENDING_SMS_DIR, 'errors');
const LOG_FILE = process.env.WHATSAPP_LOG_FILE || path.join(__dirname, 'bot.log');
const AUTH_DATA_PATH = path.join(__dirname, '.wwebjs_auth_stable');
const EXPECTED_SENDER_NUMBER = normalizePhoneNumber(process.env.WHATSAPP_SENDER_NUMBER || '962770058416');

let senderVerified = false;

ensureDir(PENDING_SMS_DIR);
ensureDir(ERROR_SMS_DIR);

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function isTransientSendError(message) {
    const text = String(message || '').toLowerCase();
    return (
        text.includes('detached frame') ||
        text.includes('execution context was destroyed') ||
        text.includes('protocol error') ||
        text.includes('target closed') ||
        text.includes('session closed') ||
        text.includes('navigation failed')
    );
}

async function sendMessageWithRetry(jid, message, normalizedPhone) {
    const maxAttempts = 3;
    let lastError = null;

    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
        try {
            if (attempt > 1) {
                log(`Retry ${attempt}/${maxAttempts} for ${normalizedPhone}`);
            }
            await client.sendMessage(jid, String(message));
            return;
        } catch (error) {
            lastError = error;
            const transient = isTransientSendError(error.message);
            log(`Send attempt ${attempt}/${maxAttempts} failed for ${normalizedPhone}: ${error.message}`);

            if (!transient || attempt === maxAttempts) {
                break;
            }

            await sleep(1200 * attempt);
        }
    }

    throw lastError || new Error('Unknown send error');
}

function moveQueueFileToErrors(filePath) {
    try {
        const targetName = `${Date.now()}_${path.basename(filePath)}`;
        const targetPath = path.join(ERROR_SMS_DIR, targetName);
        fs.renameSync(filePath, targetPath);
        log(`Queue file moved to errors: ${targetName}`);
        return true;
    } catch (error) {
        log(`Failed to move queue file to errors: ${error.message}`);
        return false;
    }
}

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
    let shouldDeleteQueueFile = true;
    let sendSucceeded = false;

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
        await sendMessageWithRetry(jid, orderData.message, normalized);
        log(`Message sent to ${normalized}`);
        sendSucceeded = true;
    } catch (error) {
        log(`Failed processing ${path.basename(filePath)}: ${error.message}`);

        if (fs.existsSync(filePath)) {
            const moved = moveQueueFileToErrors(filePath);
            if (moved) {
                shouldDeleteQueueFile = false;
            }
        }
    } finally {
        if (shouldDeleteQueueFile && fs.existsSync(filePath)) {
            try {
                fs.unlinkSync(filePath);
                if (sendSucceeded) {
                    log(`Queue file removed: ${path.basename(filePath)}`);
                } else {
                    log(`Queue file removed after failure fallback: ${path.basename(filePath)}`);
                }
            } catch (deleteError) {
                log(`Failed to remove queue file ${path.basename(filePath)}: ${deleteError.message}`);
            }
        }
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
