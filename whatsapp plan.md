# Self-Hosted Headless WhatsApp Gateway Plan (2026)

## Overview
This document outlines the best options for a self-hosted, headless WhatsApp gateway that avoids third-party dependencies like Fontte. The goal is to have full control over the API without a mandatory UI, running on your own infrastructure.

## Analysis of Options

### 1. Ready-to-Use API Servers (Recommended)
 these are "wrappers" that provide a full REST API, handling the complex WhatsApp Web socket connection logic for you.

| Feature | **WAHA (WhatsApp HTTP API)** | **Evolution API** |
| :--- | :--- | :--- |
| **Core Technology** | Puppeteer (Chrome) or Baileys | Baileys |
| **Type** | Dedicated API Server | Multi-channel API Platform |
| **API Style** | REST & Webhook | REST & Webhook |
| **Headless?** | Yes (Dashboard optional) | Yes (Dashboard optional) |
| **Pros** | Very stable, excellent documentation (Swagger), Docker-first, actively maintained. | Feature-rich (Typebot integration, etc.), efficient (native Baileys). |
| **Cons** | Slightly higher RAM usage if using the "Plus" (Chrome) version. | Can be complex if you only need simple messaging. |
| **Best For** | Pure, reliable API gateway usage. | Advanced users needing flows/integrations. |

### 2. "Build from Scratch" (DIY)
Using a library to build your own Node.js script.

| **Library** | **Baileys** |
| :--- | :--- |
| **Technology** | Raw WebSocket (no browser) |
| **Pros** | Extremely lightweight (RAM), full control, no extra "server" overhead. |
| **Cons** | You must write the HTTP API layer yourself (Express/Fastify), handle auth state, reconnection logic, and breaking changes manually. |
| **Best For** | Deep integration into an existing Node.js app, or if you need absolute minimum resource usage. |

## Recommendation
**Selection for "No Docker": Option 3 (Custom Baileys Script)**
Since you requested a non-Docker solution, the best path is to run a simple Node.js script using the **Baileys** library directly.
*   **Why?** WAHA and Evolution API rely heavily on Docker to manage dependencies (Chrome, Postgres, Redis). Installing them manually is very complex. A direct Baileys script is just one folder with a `package.json` and `index.js`, runnable with `npm start`.

## Implementation Plan (Option 3: Baileys - No Docker)

### 1. Requirements
*   **Node.js** (v18 or newer) installed on your system.
*   **npm** (comes with Node.js).
*   A folder for the project.

### 2. Setup Guide
We will create a simple REST API wrapper around Baileys.

1.  **Initialize Project:**
    ```bash
    mkdir my-whatsapp-gateway
    cd my-whatsapp-gateway
    npm init -y
    npm install @whiskeysockets/baileys express qrcode-terminal pino
    ```

2.  **Create `index.js` (The Gateway):**
    We will create a script that:
    *   Starts an Express.js web server.
    *   Connects to WhatsApp using Baileys.
    *   Saves session state locally (in an `auth_info` folder).
    *   Exposes API endpoints (e.g., `/send-message`).

3.  **Run:**
    ```bash
    node index.js
    ```
    Scan the QR code printed in the terminal.

### 3. API Usage (Custom)
Once running, you can call your own API:
*   **Send Message:** `POST http://localhost:3000/send`

## Integration with Cekat Client (cekat-saas)

Your existing application uses `WhatsAppManager` which is tightly coupled to `FonnteService`. To integrate this new gateway, we need to:

1.  **Refactor `WhatsAppManager`**: Change it to support multiple drivers (e.g., `Fonnte` and `CustomBaileys`).
2.  **Create `BaileysService`**: A new class that implements the same methods as `FonnteService` (sendMessage, getQR, etc.) but talks to your new self-hosted gateway.
3.  **Update Webhook**: The self-hosted gateway must send webhooks in the **exact same format** as Fonnte, OR we update `Api\WhatsAppWebhookController` to handle a new payload format. **Recommendation:** Make the gateway send Fonnte-compatible webhooks to minimize changes in Laravel.

### Recommended Architecture
```
[ Cekat Laravel App ] <--(HTTP API)--> [ Custom Node.js Gateway ] <--(WebSocket)--> [ WhatsApp ]
```

### Next Steps for Integration
1.  **Build the Gateway**: Set up the Node.js script (Option 3).
2.  **Test API**: Verify you can send/receive messages via Postman/Curl.
3.  **Refactor Laravel**: Update `WhatsAppManager` to use the new driver.
