import { makePASocket, DisconnectReason, useMultiFileAuthState } from '@whiskeysockets/baileys';
import express from 'express';
import pino from 'pino';
import QRCode from 'qrcode';

const app = express();
const port = 3000;

app.use(express.json());

let sock;
let qrCodeData = null;
let connectionStatus = 'disconnected'; // disconnected, qr_ready, connecting, connected

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('auth_info_baileys');

    sock = makePASocket({
        printQRInTerminal: true,
        auth: state,
        logger: pino({ level: 'silent' }),
    });

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            qrCodeData = qr;
            connectionStatus = 'qr_ready';
            console.log('QR Code received');
        }

        if (connection === 'close') {
            const shouldReconnect = (lastDisconnect.error)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('connection closed due to ', lastDisconnect.error, ', reconnecting ', shouldReconnect);

            // Only update status if truly disconnected/logged out
            if (!shouldReconnect) {
                connectionStatus = 'disconnected';
                qrCodeData = null;
            } else {
                connectionStatus = 'connecting'; // Reconnecting
            }

            if (shouldReconnect) {
                // If reconnecting immediately, don't clear QR unless we are sure. 
                // However, usually a new QR comes with a new connection attempt if needed.
                connectToWhatsApp();
            }
        } else if (connection === 'open') {
            console.log('opened connection');
            connectionStatus = 'connected';
            qrCodeData = null;
        }
    });

    sock.ev.on('creds.update', saveCreds);
}

// API Endpoints for Laravel
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

    if (connectionStatus !== 'connected') {
        return res.status(400).json({ error: 'WhatsApp is not connected' });
    }

    try {
        const id = number + '@s.whatsapp.net';
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

app.listen(port, () => {
    console.log(`WA Gateway listening on port ${port}`);
    connectToWhatsApp();
});
