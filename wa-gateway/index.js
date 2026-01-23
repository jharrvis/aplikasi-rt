const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason
} = require('@whiskeysockets/baileys');
const express = require('express');
const qrcode = require('qrcode-terminal');
const pino = require('pino');
const fs = require('fs');
const axios = require('axios');

const app = express();
app.use(express.json());

const PORT = 3000;
const SESSION_DIR = 'auth_info_baileys';

// Logger
const logger = pino({ level: 'info' });

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR);

    const sock = makeWASocket({
        printQRInTerminal: true,
        auth: state,
        logger: logger
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('QR Code received, please scan!');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            console.log('Connection closed. Status:', statusCode, 'Reconnecting:', shouldReconnect);

            // Auto-cleanup corrupted session (428 Precondition Required)
            if (statusCode === 428) {
                console.log('Session corrupted (428). Cleaning up...');
                try {
                    fs.rmSync('./' + SESSION_DIR, { recursive: true, force: true });
                    console.log('Session deleted. Will request new QR on reconnect.');
                } catch (e) {
                    console.error('Failed to delete session:', e.message);
                }
            }

            if (shouldReconnect) {
                console.log('Reconnecting in 5 seconds...');
                setTimeout(connectToWhatsApp, 5000); // Delay reconnect
            }
        } else if (connection === 'open') {
            console.log('✅ Connection opened successfully!');

            // Heartbeat to prevent connection timeout
            setInterval(() => {
                if (sock.ws?.readyState === 1) {
                    console.log('💓 Heartbeat ping...');
                }
            }, 60000); // Every 60 seconds
        }
    });

    // Session tracking for group chats (5 min timeout)
    const sessions = {};
    const SESSION_TIMEOUT = 5 * 60 * 1000; // 5 minutes
    const KEYWORDS = ['lapor', 'rekap', 'koreksi', 'cek', 'siapa', 'ngadmin', 'admin', 'min', 'mimin'];

    sock.ev.on('messages.upsert', async m => {
        const msg = m.messages[0];
        if (!msg.key.fromMe && m.type === 'notify') {
            const text = msg.message?.conversation || msg.message?.extendedTextMessage?.text;
            const jid = msg.key.remoteJid;
            const isGroup = jid.endsWith('@g.us');

            console.log('New message received:', text, 'from:', jid);

            if (!text) return;

            // For groups: check keyword or active session
            if (isGroup) {
                const hasKeyword = KEYWORDS.some(k => text.toLowerCase().includes(k));
                const isSessionActive = sessions[jid] && (Date.now() - sessions[jid] < SESSION_TIMEOUT);

                if (!hasKeyword && !isSessionActive) {
                    console.log('Skipping group message - no keyword and session inactive');
                    return;
                }

                // Refresh session
                sessions[jid] = Date.now();
                console.log(`Session refreshed for ${jid}, active for 5 more minutes`);
            }

            // Forward to backend
            try {
                await axios.post('http://localhost:8000/api/webhook/whatsapp', {
                    message: text,
                    from: jid,
                    messages: [msg]
                });
                console.log('Webhook sent to backend');
            } catch (err) {
                console.error('Webhook failed:', err.message);
            }
        }
    });

    // API Endpoint to send message
    app.post('/send-message', async (req, res) => {
        const { number, message } = req.body; // number format: 628123456789@s.whatsapp.net or just 628123456789

        if (!number || !message) {
            return res.status(400).json({ status: 'error', message: 'Missing number or message' });
        }

        const jid = number.includes('@') ? number : `${number}@s.whatsapp.net`;

        try {
            await sock.sendMessage(jid, { text: message });
            res.json({ status: 'success', message: 'Message sent' });
        } catch (error) {
            console.error('Failed to send message:', error);
            res.status(500).json({ status: 'error', message: 'Failed to send message' });
        }
    });

    return sock;
}

// Start the gateway
connectToWhatsApp();

app.listen(PORT, () => {
    console.log(`WhatsApp Gateway running on port ${PORT}`);
});
