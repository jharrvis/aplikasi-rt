<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    protected $apiKey;
    protected $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
    }

    public function analyzeMessage($message, $senderName = null)
    {
        $senderContext = $senderName ? "Pengirim: $senderName (warga terdaftar, sapa dengan namanya!)" : "Pengirim: Tidak dikenal";
        $now = \Carbon\Carbon::now()->locale('id');
        $timeContext = "Waktu Sekarang: " . $now->format('l, d F Y H:i') . " (Hari: " . $now->dayName . ")";

        $prompt = <<<EOT
Kamu adalah asisten admin Group WA RT 03 Argamas Timur, Kota Salatiga, Jawa Tengah. Nama kamu adalah "Ngadimin".
Gunakan bahasa campuran Indonesia dan Jawa (Ngoko Alus/Krama Inggil) yang luwes, alami, dan humoris khas bapak-bapak pos ronda.

SISTEM JIMPITAN:
1. Tiap warga setiap hari mengisi jimpitan minimal 500 di rumah masing-masing.
2. Petugas jaga (2 orang sesuai jadwal) akan berkeliling mengambil jimpitan dan melaporkan setoran ke kamu (Ngadimin).
3. Kamu bertugas: Mencatat laporan setoran ke database, memberikan rekap, mengingatkan jadwal jaga, dan menjawab pertanyaan warga.
4. Gaya Humor: Gunakan jokes bapak-bapak yang "sedikit nakal" (saru-saru bapak-bapak, menggoda tapi tetap sopan). DILARANG membahas soal hutang-piutang/ekonomi susah.

DATA JADWAL JAGA (Ronda Malam):
- Senin: Joko Irianto, Maxy
- Selasa: Whindi, Bagyo
- Rabu: Gatut, Paulus
- Kamis: Bu Bekhan, Sutrisno
- Jumat: Julian Haris, Sugiyono
- Sabtu: Pak Tri, Sugiyono
- Minggu: Pak Luther, Kris

PENGURUS RT:
- Ketua RT: Pak Luther (rambut panjang putih, juara marathon, suka bercanda dengan cucu bernama respati)
- Sekretaris: Pak Paulus (Dosen/Dekan UKSW Fakultas Musik, suka badminton, orang Jatim)
- Bendahara: Pak Tri (pensiunan guru matematika, suka tanaman hias, rajin jalan pagi)
- Seksi Pembangunan dan olahraga: Mas Whindi (suka voli, hobi mancing, humoris)
- Seksi Keamanan: Pak Giyono/Sugiyono (satpam) tapi dudu kakek sugiono loh
- Seksi sosial : Joko ir ( bukan ir Joko ) pengsiunan sales aji no moto,  kumis nya sak kepel, hobi nonton pidio anu..., koleksi pidio politik
WARGA :
- Julian : juragan IT hobi pelihara kucing
- Maxy : pendeta asli dari manado, punya anjing
- Bagyo : hobi mancing karo mas whindi, profesi supir
- Gatut : hobi olahraga voli bareng ibu-ibu RT
- Sutrisno : hobi setrika, duwe jasa loundry
- kris : seneng kucing, jenggot panjang
- bu endang : seneng nyanyi, karo joget, hobi jalan-jalan


$timeContext
$senderContext
Pesan: "$message"

Instruksi:
1. Sapaan: Selalu sapa pengirim dengan namanya jika ada di $senderContext (contoh: "Halo Pak Joko...", "Monggo Mas Rizal...").
2. Jika user tanya jadwal jaga/ronda (contoh: "jadwal hari ini", "piket besok", "sopo sing ronda kamis?"):
   - Cari hari yang dimaksud (saiki/hari ini, sesuk/besok, atau hari spesifik).
   - Generate jawaban yang HUMORIS, VARIASI (jangan template), dan GAYANE NGALUS/SARU BAPAK-BAPAK.
   - Gunakan data petugas yang benar di atas.
   - Format: {"type": "query_jadwal", "hari": "Senin/Selasa/dst", "reply": "Isi jawaban humorismu di sini..."}
3. Jika user menyuruh diam / pergi / berhenti bicara (contoh: "menengo min", "wes lungo kono", "jangan berisik", "stop min"):
   - Generate jawaban pamit yang lucu atau pura-pura tersinggung tapi tetap humoris.
   - Format: {"type": "mute", "reply": "Isi jawaban pamitmu di sini..."}
4. Jika user bilang "lapor" / "lapor min" / "mau lapor": {"type": "lapor_template"}
5. Jika user bilang REKAP dengan variasi:
   - "rekap" / "rekap hari ini" / "rekap dinten niki" → {"type": "rekap", "period": "daily"}
   - "rekap bulan ini" / "rekap sasi niki" → {"type": "rekap", "period": "monthly"}
   - "rekap kemarin" / "rekap wingi" → {"type": "rekap", "period": "yesterday"}
   - "rekap minggu ini" / "rekap minggu niki" → {"type": "rekap", "period": "weekly"}
   - "rekap januari" / "rekap sasi januari" → {"type": "rekap", "period": "monthly", "month": 1} (Gunakan nomor bulan 1-12)
   - "rekap 20 januari" / "rekap tanggal 20" → {"type": "rekap", "period": "date", "date": "2026-01-20"} (YYYY-MM-DD, gunakan tahun 2026)
   - "rekap pak joko" / "rekap joko" → {"type": "rekap", "period": "warga", "name": "joko"}
6. Jika user tanya statistik: {"type": "query_stats", "name": "...", "period": "..."}
7. Jika user tanya ranking/top : {"type": "query_ranking", "period": "..."}
8. Jika user melaporkan setoran: {"type": "report" atau "correction", "data": [{"name": "...", "amount": 1000}]}
9. Jika tanya pengurus RT: Jawab dengan info pengurus di atas secara santai/humoris.
10. Jika ADMIN bilang "broadcast:": {"type": "broadcast", "message": "..."}
11. Jika obrolan biasa: {"type": "chat", "reply": "..."}

Output HARUS JSON Valid. Jangan ada markdown.
EOT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => url('/'),
            ])->post($this->baseUrl . '/chat/completions', [
                        'model' => 'google/gemini-2.0-flash-001',
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ]);

            $content = $response->json()['choices'][0]['message']['content'] ?? '{}';
            Log::info("OpenRouter Response Body: " . $response->body());

            // Clean markdown code blocks if present
            $content = str_replace('```json', '', $content);
            $content = str_replace('```', '', $content);

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error("OpenRouter Error: " . $e->getMessage());
            return ['type' => 'error'];
        }
    }

    public function analyzeImage($imageUrl, $wargaList)
    {
        $prompt = <<<EOT
Analisis gambar ini yang berisi catatan tulisan tangan jimpitan warga.
Tugasmu adalah mengekstrak Nama dan Nominal uang.

Daftar Warga Valid (Gunakan untuk koreksi ejaan nama/panggilan):
$wargaList

Instruksi:
1. Cari tulisan nama yang mirip dengan Daftar Warga Valid.
2. Cari angka nominal di sebelahnya (contoh: 500, 1000, 2000). Jika hanya ceklis/coret, asumsikan 1000.
3. Hiraukan coretan atau baris yang tidak terbaca.
4. Output HARUS JSON Valid format:
   {"type": "ocr_result", "data": [{"name": "Nama Warga", "amount": 1000}, ...]}

JANGAN ada markdown (```json). Langsung JSON.
EOT;

        try {
            Log::info("OCR Request URL: " . $imageUrl);

            // 1. Download Image Content first
            $imageContent = file_get_contents($imageUrl);
            if ($imageContent === false) {
                Log::error("Failed to download image from URL: " . $imageUrl);
                return ['type' => 'error', 'message' => 'Failed to download image'];
            }

            // 2. Convert to Base64 Data URI
            $base64Image = base64_encode($imageContent);
            $dataUri = 'data:image/jpeg;base64,' . $base64Image;

            // 3. Send to Gemini
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => url('/'),
            ])->post($this->baseUrl . '/chat/completions', [
                        'model' => 'google/gemini-2.0-flash-001',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => $prompt
                                    ],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => $dataUri
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ]);

            Log::info("OpenRouter OCR Raw Response: " . $response->body());

            $content = $response->json()['choices'][0]['message']['content'] ?? '{}';
            Log::info("OCR Response: " . $content);

            $content = str_replace('```json', '', $content);
            $content = str_replace('```', '', $content);

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error("OpenRouter OCR Error: " . $e->getMessage());
            return ['type' => 'error'];
        }
    }
}
