import { makeWASocket, DisconnectReason, useMultiFileAuthState, downloadMediaMessage } from '@whiskeysockets/baileys';
import express from 'express';
import pino from 'pino';
import QRCode from 'qrcode';
import fs from 'fs';

console.log(`Node version: ${process.version}`);

const app = express();
const port = 3000;
const SESSION_DIR = 'auth_info_baileys';

app.use(express.json({ limit: '100mb' }));

let sock;
let qrCodeData = null;
let connectionStatus = 'disconnected';

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR);

    sock = makeWASocket({
        auth: state,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: true,
        browser: ['Aplikasi RT', 'Chrome', '1.0.0'],
        connectTimeoutMs: 60000,
        keepAliveIntervalMs: 10000,
        syncFullHistory: false,
    });

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;
        if (qr) {
            qrCodeData = qr;
            connectionStatus = 'qr_ready';
            console.log('QR Code received');
        }
        if (connection === 'close') {
            const statusCode = (lastDisconnect.error)?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            console.log(`Connection closed. Status: ${statusCode}, Reconnecting: ${shouldReconnect}`);
            connectionStatus = 'disconnected';
            qrCodeData = null;
            if (statusCode === 428 || statusCode === 405) {
                console.log(`Session corrupted (${statusCode}). Cleaning up...`);
                try {
                    fs.rmSync('./' + SESSION_DIR, { recursive: true, force: true });
                } catch (e) {
                    console.error('Failed to delete session:', e.message);
                }
            }
            if (shouldReconnect) {
                setTimeout(connectToWhatsApp, 3000);
            }
        } else if (connection === 'open') {
            console.log('Opened connection');
            connectionStatus = 'connected';
            qrCodeData = null;
        }
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('messages.upsert', async (m) => {
        try {
            console.log(`\n\n--- INCOMING UPSERT (Type: ${m.type}) ---`);
            const msg = m.messages[0];
            if (!msg.key.fromMe && m.type === 'notify') {

                // Deep clone the object to strip Protobuf classes and get a clean JS object
                // This ensures our custom properties (like .base64) are not stripped by JSON.stringify
                const payload = JSON.parse(JSON.stringify(m));
                const cleanMsg = payload.messages[0];

                const messageContent = cleanMsg.message;
                const imageMessage = messageContent?.imageMessage ||
                    messageContent?.viewOnceMessage?.message?.imageMessage ||
                    messageContent?.viewOnceMessageV2?.message?.imageMessage ||
                    messageContent?.ephemeralMessage?.message?.imageMessage;

                if (imageMessage) {
                    console.log('🖼️ Image detected, decrypting...');
                    try {
                        const buffer = await downloadMediaMessage(
                            msg, // Use the original 'msg' instance for Baileys helper
                            'buffer',
                            {},
                            {
                                logger: pino({ level: 'silent' }),
                                reuploadRequest: sock.updateMediaMessage
                            }
                        );
                        // Attach base64 to our CLONED payload
                        imageMessage.base64 = buffer.toString('base64');
                        console.log(`✅ Decrypted! Buffer: ${buffer.length} bytes. Base64 length: ${imageMessage.base64.length}`);
                    } catch (err) {
                        console.error('❌ Failed to decrypt image:', err.message);
                    }
                }

                console.log('Forwarding to Laravel Webhook...');
                const WEBHOOK_URL = 'https://bot.cekat.biz.id/api/webhook/whatsapp';

                const response = await fetch(WEBHOOK_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload) // Forward the modified CLONE
                });

                console.log('Webhook Response Status:', response.status);
            }
        } catch (error) {
            console.error('Error in upsert handler:', error);
        }
    });
}

app.get('/status', (req, res) => {
    res.json({ status: connectionStatus });
});

app.get('/qr', async (req, res) => {
    if (qrCodeData) {
        try {
            const qrImage = await QRCode.toDataURL(qrCodeData);
            res.json({ qr: qrImage, raw: qrCodeData });
        } catch (err) {
            res.status(500).json({ error: 'Failed to generate QR image' });
        }
    } else {
        res.status(404).json({ error: 'QR Code not available' });
    }
});

app.post('/send-message', async (req, res) => {
    const { number, message } = req.body;
    if (connectionStatus !== 'connected') return res.status(400).json({ error: 'WhatsApp is not connected' });
    try {
        const id = number.includes('@') ? number : `${number}@s.whatsapp.net`;
        const sentMsg = await sock.sendMessage(id, { text: message });
        res.json({ status: 'success', data: sentMsg });
    } catch (error) {
        console.error('Failed to send message:', error);
        res.status(500).json({ error: 'Failed to send message' });
    }
});

app.post('/logout', async (req, res) => {
    try {
        await sock.logout();
        res.json({ status: 'success' });
    } catch (error) {
        res.status(500).json({ error: 'Failed to logout' });
    }
});

app.listen(port, '0.0.0.0', () => {
    console.log(`WA Gateway listening on port ${port}`);
    connectToWhatsApp();
});
