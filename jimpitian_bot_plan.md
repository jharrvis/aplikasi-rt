# Plan Aplikasi Chatbot WA RT (Jimpitian & Reminder)

## 1. Overview
Aplikasi ini bertujuan untuk mengotomatisasi pencatatan "Jimpitian" dan pengingat jadwal jaga warga (Ronda/Jimpitian) menggunakan WhatsApp. Aplikasi akan menggunakan AI (OpenRouter) untuk memahami laporan warga dan menyusun kata-kata pengingat yang natural.

Mengikuti referensi `whatsapp plan.md`, kita akan menggunakan pendekatan **Self-Hosted Headless Gateway** tanpa Docker.

## 2. Arsitektur Sistem

Sistem terdiri dari 3 komponen utama:

1.  **WhatsApp Gateway (Node.js + Baileys)**:
    *   Bertugas menjaga koneksi ke WhatsApp.
    *   Menerima pesan masuk -> Forward ke Backend via Webhook.
    *   Menerima request kirim pesan dari Backend -> Kirim ke WhatsApp.
2.  **Backend Logic (Laravel/PHP)**:
    *   Menyimpan data warga, jadwal, dan transaksi jimpitian (MySQL).
    *   Memproses logika bisnis.
    *   Berinteraksi dengan OpenRouter AI.
    *   Menjalankan Scheduler (Cron) untuk pengingat otomatis.
3.  **AI Service (OpenRouter)**:
    *   **NLU (Natural Language Understanding)**: Mengekstrak data dari laporan warga (misal: "Lapor jimpitian rumah pak budi 2000" -> `{name: "Budi", amount: 2000}`).
    *   **NLG (Natural Language Generation)**: Membuat kalimat pengingat yang variatif dan sopan untuk jadwal jaga.

```mermaid
graph LR
    User[Warga/Group WA] <-->|WA| Gateway[Node.js Gateway]
    Gateway <-->|HTTP API/Webhook| Backend[Aplikasi RT (Laravel)]
    Backend <-->|API| AI[OpenRouter]
    Backend <-->|SQL| DB[(Database)]
```

## 3. Database Schema

### a. `warga` (Data Warga)
*   `id`
*   `nama` (Nama lengkap)
*   `panggilan` (Nama panggilan untuk bot)
*   `no_hp` (Nomor WA, format 628...)
*   `no_hp` (Nomor WA, format 628...)
*   `nomor_rumah` (Nomor rumah warga)

### b. `jadwal_jaga` (Jadwal Petugas)
*   `id`
*   `hari` (Senin, Selasa, dll)
*   `warga_id` (FK ke warga)
*   `jenis_tugas` (Jimpitian / Ronda)

### c. `transaksi_jimpitian` (Laporan Masuk)
*   `id`
*   `tanggal` (Date)
*   `warga_id` (FK ke warga - siapa yang bayar)
*   `pelapor_id` (FK ke warga - siapa yang lapor, opsional)
*   `nominal` (Decimal)
*   `keterangan` (Raw text)
*   `status` (Verified/Pending)

## 4. Alur Kerja (Workflows)

### A. Pencatatan Jimpitian (AI Powered & Conversational)
1.  **Trigger 1 (Direct)**: Warga langsung lapor.
    *   *Contoh*: "Lapor joko 1000, agus 2000"
    *   *Action*: Bot langsung proses dan balas rekap.

2.  **Trigger 2 (Conversational)**: Warga mulai dengan salam/keyword pembuka.
    *   *User*: "Lapor min" atau "Lapor jimpitian"
    *   *Bot (AI)*: "Njih, monggo. Siap mencatat. Silakan sebutkan nama dan nominalnya." (Context aware response).
    *   *User*: "Pak RT 5000"
    *   *Bot*: "Pak RT Rp 5.000 masuk. ✅ Ada lagi?"
    *   *User*: "Sama bu Tejo 2000"
    *   *Bot*: "Bu Tejo Rp 2.000 masuk. Total sesi ini: Rp 7.000."

3.  **Process Logic**:
    *   Setiap pesan di Grup di-analisa oleh AI untuk menentukan:
        *   **Intent**: Apakah ini 'Membuka Laporan', 'Melaporkan Data', atau 'Obrolan Biasa'.
        *   **Extraction**: Ambil Nama & Nominal jika ada.
    *   **Prompt NLU (Updated)**:
        > "Kamu adalah asisten RT yang ramah (Basa Jawa halus ok). Tugasmu:
        > 1. Jika user bilang 'lapor', jawab ramah persilakan lapor.
        > 2. Jika user lapor data (misal 'joko 1000'), ekstrak JSON: `[{name: 'joko', amount: 1000}]`.
        > 3. Jika user lapor banyak, ekstrak semua."
    *   **Name Resolver**: Fuzzy match `nama` input vs `nomor_rumah` / `nama` di DB.

### B. Pengingat Jadwal (Jimpitian/Ronda)
1.  **Trigger**: Cron Job harian (misal jam 17.00 WIB).
2.  **Process**:
    *   Backend cek DB `jadwal_jaga` untuk hari esok/malam ini.
    *   Ambil daftar nama petugas.
    *   Backend memanggil OpenRouter untuk membuat pesan pengingat.
    *   **Prompt NLG**:
        > "Buatkan pesan pengingat WhatsApp yang ramah dan santai untuk grup RT. Ingatkan bahwa petugas jimpitian malam ini adalah: [Daftar Nama]. Tambahkan emoji semangat."
3.  **Action**:
    *   Hasil teks dari AI dikirim ke Grup WA via Gateway.

## 5. Implementasi Plan

### Phase 1: Setup WhatsApp Gateway (Sesuai `whatsapp plan.md`)
- [ ] Install Node.js & Baileys di folder `wa-gateway`.
- [ ] Buat script `index.js` untuk koneksi WA.
- [ ] Buat endpoint API `/send-message`.
- [ ] Buat webhook mechanism untuk forward pesan masuk.

### Phase 2: Backend & Database
- [ ] Setup Project Laravel `aplikasi-rt`.
- [ ] Setup Model & Migration (`Warga`, `Jadwal`, `Transaksi`).
- [ ] Buat Controller `WebhookController` untuk handle data dari Gateway.
- [ ] Setup `OpenRouterService` di Laravel.

### Phase 3: AI Integration
- [ ] Design Prompt untuk Parsing Laporan Jimpitian.
- [ ] Design Prompt untuk Generator Pesan Pengingat.
- [ ] Test akurasi AI dengan berbagai contoh pesan teks.

### Phase 4: Scheduler & Testing
- [ ] Setup Laravel Schedule (Cron) untuk pengingat harian.
- [ ] Uji coba flow Lapor -> Catat -> Reply.
- [ ] Uji coba flow Pengingat Otomatis.

## 6. Token/Cost Estimation
*   **OpenRouter**: Menggunakan model hemat biaya namun cerdas seperti `google/gemini-2.0-flash-exp` atau `meta-llama/llama-3-8b-instruct`.
*   Biaya diperkirakan sangat rendah (< $1/bulan) untuk volume chat grup RT.

---
**Catatan**: Pastikan file `.env` menyimpan API Key OpenRouter dan URL Gateway dengan aman.
