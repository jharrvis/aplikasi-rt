# Task: RT Jimpitian Chatbot Implementation

- [x] **Planning**
    - [x] Create project plan (`jimpitian_bot_plan.md`) <!-- id: 0 -->
    - [x] Update plan with bulk reporting & conversational flow <!-- id: 1 -->

- [x] **Phase 1: WhatsApp Gateway**
    - [x] Setup Node.js project (`wa-gateway`) <!-- id: 2 -->
    - [x] Implement Baileys connection logic <!-- id: 3 -->
    - [x] Implement QR Code display <!-- id: 4 -->
    - [x] specific JID handling fix (LID support) <!-- id: 5 -->

- [x] **Phase 2: Backend & Database**
    - [x] Setup Laravel project (`backend`) <!-- id: 6 -->
    - [x] Configure Database & Migrations (`wargas`, `jadwals`, `transaksis`) <!-- id: 7 -->
    - [x] Setup Models <!-- id: 8 -->
    - [x] Seed Initial Data (`WargaSeeder`) <!-- id: 9 -->

- [ ] **Phase 3: Logic & Integration**
    - [x] Implement `OpenRouterService` (AI) <!-- id: 10 -->
    - [x] Implement `WebhookController` <!-- id: 11 -->
    - [x] Debug Reporting Flow (`Joko 1000`) <!-- id: 12 -->
    - [x] Implement Fuzzy Name Matching <!-- id: 13 -->
    - [x] Implement Duplicate Validation & Correction <!-- id: 16 -->
    - [x] Support 'Kosong' (Zero Value) Reports <!-- id: 17 -->
    - [x] Implement Recap Functionality <!-- id: 18 -->
    - [ ] **Enhance Recap (Detailed List)** <!-- id: 19 -->
    - [ ] **Implement Specific Queries (e.g. "Joko bulan ini")** <!-- id: 20 -->
    - [ ] **Implement Security (Time Limit & Reporter Validation)** <!-- id: 21 -->
    - [ ] **Normalize Phone Numbers for Auth** <!-- id: 23 -->
    - [ ] **Update Persona (Javanese & Funny)** <!-- id: 22 -->

- [ ] **Phase 4: Scheduler (Reminder)**
    - [ ] Create Reminder Job <!-- id: 14 -->
    - [ ] Configure Cron <!-- id: 15 -->
