<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Services\OpenRouterService;
use App\Models\Warga;
use App\Models\TransaksiJimpitian;
use App\Models\Admin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookController extends Controller
{
    protected $wa;
    protected $ai;

    public function __construct(WhatsAppService $wa, OpenRouterService $ai)
    {
        $this->wa = $wa;
        $this->ai = $ai;
    }

    public function handle(Request $request)
    {
        $data = $request->all();
        $msg = null;
        $chatId = null;
        $senderId = null;
        $imageUrl = null;
        $imageBase64 = null;

        // Parse Message (Baileys Adapter)
        if (isset($data['messages'][0])) {
            $m = $data['messages'][0];
            if (!($m['key']['fromMe'] ?? false)) {
                $msg = $m['message']['conversation'] ?? $m['message']['extendedTextMessage']['text'] ?? null;
                $chatId = $m['key']['remoteJid'] ?? null;
                $senderId = $m['key']['participant'] ?? $chatId;

                // Image Handling
                if (isset($m['message']['imageMessage'])) {
                    $img = $m['message']['imageMessage'];
                    $msg = $img['caption'] ?? '[IMAGE]';
                    $imageUrl = $img['url'] ?? null;
                    $imageBase64 = $img['base64'] ?? null; // Added by our gateway
                }
            }
        } elseif (isset($data['message']) && isset($data['from'])) {
            // Fallback
            $msg = $data['message'];
            $chatId = $data['from'];
            $senderId = $data['sender'] ?? $chatId;

            if (($data['type'] ?? '') === 'image' && isset($data['url'])) {
                $imageUrl = $data['url'];
                $msg = $data['caption'] ?? '[IMAGE]';
                $imageBase64 = $data['base64'] ?? null;
            }
        }

        if ((!$msg && !$imageUrl && !$imageBase64) || !$chatId)
            return response()->json(['status' => 'ignored']);

        // Identify sender
        $senderPhone = $this->normalizePhone($senderId);
        $knownWarga = Warga::where('no_hp', $senderPhone)->first();
        $senderName = $knownWarga ? ($knownWarga->panggilan ?? $knownWarga->nama) : null;

        // --- Silence Logic ---
        $cacheKey = "bot_muted_" . $chatId;
        $isMuted = Cache::has($cacheKey);

        // Check for wake-up triggers
        $wakeTriggers = ['ngadimin', 'tangi', 'halo min', 'pagi min', 'siang min', 'sore min', 'malam min', 'oy min'];
        $shouldWake = false;
        foreach ($wakeTriggers as $trigger) {
            if (stripos($msg ?? '', $trigger) !== false) {
                $shouldWake = true;
                break;
            }
        }

        if ($isMuted && !$shouldWake) {
            return response()->json(['status' => 'muted']);
        }

        if ($shouldWake) {
            Cache::forget($cacheKey);
        }
        // --- End Silence Logic ---

        // AI Analysis
        if ($imageUrl || $imageBase64) {
            // OCR Flow
            $wargaList = Warga::pluck('nama')->implode(", ");
            $analysis = $this->ai->analyzeImage($imageUrl, $wargaList, $imageBase64);
            Log::info("OCR Analysis:", $analysis ?? []);
        } else {
            // Text Flow
            $analysis = $this->ai->analyzeMessage($msg, $senderName);
        }

        Log::info("AI Analysis Result:", $analysis ?? []);

        if (empty($analysis) || !isset($analysis['type']))
            return response()->json(['status' => 'no_action']);

        // Route Action
        if (($analysis['type'] ?? '') === 'ocr_error') {
            $this->wa->sendMessage($chatId, "⚠️ " . ($analysis['message'] ?? 'Gagal membaca gambar.'));
        } elseif ($analysis['type'] === 'ocr_result') {
            $this->handleReport($chatId, $senderId, $analysis);
        } elseif ($analysis['type'] === 'report' || $analysis['type'] === 'correction') {
            $this->handleReport($chatId, $senderId, $analysis);
        } elseif ($analysis['type'] === 'rekap') {
            $this->handleRecap($chatId, $analysis);
        } elseif ($analysis['type'] === 'lapor_template') {
            $this->handleLaporTemplate($chatId);
        } elseif ($analysis['type'] === 'query_stats') {
            $this->handleQueryStats($chatId, $analysis);
        } elseif ($analysis['type'] === 'query_ranking') {
            $this->handleLeaderboard($chatId, $analysis);
        } elseif ($analysis['type'] === 'query_jadwal') {
            $this->handleJadwal($chatId, $analysis);
        } elseif ($analysis['type'] === 'broadcast') {
            $this->handleBroadcast($chatId, $senderId, $analysis);
        } elseif ($analysis['type'] === 'mute') {
            Cache::put($cacheKey, true, now()->addHours(24));
            if (isset($analysis['reply'])) {
                $this->wa->sendMessage($chatId, $analysis['reply']);
            }
        } elseif ($analysis['type'] === 'chat' && isset($analysis['reply'])) {
            $this->wa->sendMessage($chatId, $analysis['reply']);
        }

        return response()->json(['status' => 'processed']);
    }

    protected function handleReport($chatId, $senderId, $analysis)
    {
        $reply = "";
        $total = 0;
        $isCorrection = ($analysis['type'] === 'correction');
        $senderPhone = $this->normalizePhone($senderId);

        $reporter = Warga::where('no_hp', $senderPhone)->first();
        $isAdmin = Admin::where('phone', $senderPhone)->exists() || $senderPhone === '6285326483431';

        if (!$reporter && env('APP_ENV') !== 'local') {
            Log::warning("Unregistered reporter: $senderPhone");
        }

        foreach ($analysis['data'] as $item) {
            $name = $item['name'] ?? '';
            $amount = intval($item['amount'] ?? 0);

            $warga = $this->findWargaFuzzy($name);

            if ($warga) {
                // Check Duplicate
                $existing = TransaksiJimpitian::where('warga_id', $warga->id)
                    ->whereDate('tanggal', now())
                    ->first();

                if ($existing) {
                    if (!$isCorrection && ($analysis['type'] !== 'ocr_result')) {
                        $reply .= "⚠️ *{$warga->nama}*: Sampun laporan (Rp " . number_format($existing->nominal) . ").\nKetik 'Koreksi...' menawi lepat.\n";
                        continue;
                    }

                    $existing->update(['nominal' => $amount, 'keterangan' => 'Koreksi Via Bot', 'pelapor_id' => $reporter->id ?? null]);
                    $reply .= "✏️ *{$warga->nama}*: Datane pun kulo ganti dadi Rp " . number_format($amount) . " ✅\n";
                } else {
                    TransaksiJimpitian::create([
                        'tanggal' => now(),
                        'warga_id' => $warga->id,
                        'nominal' => $amount,
                        'keterangan' => 'Via Bot',
                        'status' => 'verified',
                        'pelapor_id' => $reporter->id ?? null
                    ]);
                    $reply .= "✅ *{$warga->nama}* (Rmh {$warga->nomor_rumah}): Rp " . number_format($amount) . "\n";
                }
                $total += $amount;
            } else {
                if (!empty($name)) {
                    $reply .= "❓ *{$name}*: Mboten kepanggih, cobi ejaan liyane.\n";
                }
            }
        }

        if ($total > 0)
            $reply .= "\nMaturnuwun! Total sementawis: Rp " . number_format($total) . " 🙏";
        $this->wa->sendMessage($chatId, $reply);
    }

    protected function handleRecap($chatId, $analysis)
    {
        $period = $analysis['period'] ?? 'daily';

        if ($period === 'warga') {
            $name = $analysis['name'] ?? '';
            $warga = $this->findWargaFuzzy($name);

            if (!$warga) {
                $this->wa->sendMessage($chatId, "Sinten niku '$name'? Kulo mboten kenal je.. 😅");
                return;
            }

            $transactions = TransaksiJimpitian::where('warga_id', $warga->id)
                ->orderBy('tanggal', 'desc')
                ->limit(10)
                ->get();

            $total = TransaksiJimpitian::where('warga_id', $warga->id)->sum('nominal');

            $msg = "📊 *Rekap Jimpitan {$warga->nama}*\n\n";
            $msg .= "🏠 Rumah: {$warga->nomor_rumah}\n\n";

            if ($transactions->isEmpty()) {
                $msg .= "_Dereng wonten catatan setoran._\n";
            } else {
                $msg .= "*10 Transaksi Terakhir:*\n";
                foreach ($transactions as $t) {
                    $date = \Carbon\Carbon::parse($t->tanggal)->format('d M');
                    $msg .= "• {$date}: Rp " . number_format($t->nominal) . "\n";
                }
            }

            $msg .= "\n💰 *Total Sepanjang Masa: Rp " . number_format($total) . "*";
            $this->wa->sendMessage($chatId, $msg);
            return;
        }

        $query = TransaksiJimpitian::selectRaw('warga_id, SUM(nominal) as total_nominal')
            ->with('warga');

        $title = "";

        if ($period === 'monthly') {
            $month = $analysis['month'] ?? now()->month;
            $year = $analysis['year'] ?? now()->year;
            $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            $monthName = \Carbon\Carbon::createFromDate($year, $month, 1)->locale('id')->monthName;
            $title = "Wulan " . ucfirst($monthName) . " " . $year;
        } elseif ($period === 'yesterday') {
            $query->whereDate('tanggal', now()->subDay());
            $title = "Wingi (" . now()->subDay()->format('d M Y') . ")";
        } elseif ($period === 'weekly') {
            $query->whereBetween('tanggal', [now()->startOfWeek(), now()->endOfWeek()]);
            $title = "Minggu Niki (" . now()->startOfWeek()->format('d M') . " - " . now()->endOfWeek()->format('d M Y') . ")";
        } elseif ($period === 'date') {
            $dateStr = $analysis['date'] ?? now()->format('Y-m-d');
            try {
                $date = \Carbon\Carbon::parse($dateStr);
            } catch (\Exception $e) {
                $date = now();
            }
            $query->whereDate('tanggal', $date);
            $title = "Tanggal " . $date->format('d M Y');
        } else {
            $query->whereDate('tanggal', now());
            $title = "Dinten Niki (" . now()->format('d M Y') . ")";
        }

        $transactions = $query->groupBy('warga_id')
            ->orderByDesc('total_nominal')
            ->get();

        $total = $transactions->sum('total_nominal');
        $count = $transactions->count();

        $msg = "📊 *Rekap Jimpitian $title*\n\n";

        foreach ($transactions as $idx => $t) {
            $num = $idx + 1;
            if ($t->warga) {
                $msg .= "$num. {$t->warga->nama}: Rp " . number_format($t->total_nominal) . "\n";
            }
        }

        if ($transactions->isEmpty()) {
            $msg .= "_Dereng wonten catatan setoran._\n";
        }

        $msg .= "\n💰 *Total: Rp " . number_format($total) . "*";
        $msg .= "\n📝 Jumlah: $count Warga (Sing setor)";
        $msg .= "\n\n_Maturnuwun sedoyo warga ingkang sampun tertib!_ 👍";

        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleLaporTemplate($chatId)
    {
        $wargas = Warga::orderBy('nomor_rumah')->get();
        $msg = "📝 *Template Lapor Jimpitan*\n\n";
        $msg .= "_Copy, edit nominal, kirim balik:_\n\n";

        foreach ($wargas as $w) {
            $nama = $w->panggilan ?? $w->nama;
            $msg .= "{$nama} 1000\n";
        }

        $msg .= "\n_Tips: Ganti 1000 dengan nominal sebenarnya._\n";
        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleQueryStats($chatId, $analysis)
    {
        $name = $analysis['name'] ?? '';
        $warga = $this->findWargaFuzzy($name);

        if (!$warga) {
            $this->wa->sendMessage($chatId, "Sinten niku '$name'? Kulo mboten kenal je.. 😅");
            return;
        }

        $query = TransaksiJimpitian::where('warga_id', $warga->id);
        $periodText = "sedoyo wekdal";

        if (($analysis['period'] ?? '') === 'monthly') {
            $query->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year);
            $periodText = "wulan niki";
        }

        $total = $query->sum('nominal');
        $msg = "🔎 Cek Data *{$warga->nama}* ($periodText):\n💰 Total: Rp " . number_format($total) . "\nRajin menabung pangkal kaya! 🤑";
        $this->wa->sendMessage($chatId, $msg);
    }

    protected function normalizePhone($phone)
    {
        if (str_contains($phone, '@')) {
            $phone = explode('@', $phone)[0];
        }
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }

    protected function handleLeaderboard($chatId, $analysis)
    {
        $limit = $analysis['limit'] ?? 10;
        $period = $analysis['period'] ?? 'monthly';
        $query = TransaksiJimpitian::selectRaw('warga_id, SUM(nominal) as total')
            ->groupBy('warga_id')
            ->orderByDesc('total')
            ->limit($limit);

        $periodText = "Sepanjang Masa";
        if ($period === 'monthly') {
            $query->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year);
            $periodText = "Wulan Niki";
        } elseif ($period === 'daily') {
            $query->whereDate('tanggal', now());
            $periodText = "Dinten Niki";
        }

        $rankings = $query->get();
        $msg = "🏆 *Leaderboard Jimpitan - $periodText*\n\n";
        $medals = ['🥇', '🥈', '🥉'];
        foreach ($rankings as $idx => $r) {
            $warga = Warga::find($r->warga_id);
            if ($warga) {
                $medal = $medals[$idx] ?? ($idx + 1) . ".";
                $msg .= "$medal {$warga->nama}: Rp " . number_format($r->total) . "\n";
            }
        }
        $msg .= "\n_Maturnuwun sanget kangge sedoyo! Rajin tenan panjenengan!_ 💪";
        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleJadwal($chatId, $analysis)
    {
        if (!empty($analysis['reply'])) {
            $this->wa->sendMessage($chatId, $analysis['reply']);
            return;
        }

        $hariInput = $analysis['hari'] ?? \Carbon\Carbon::now()->locale('id')->dayName;
        $dayMap = ['monday' => 'senin', 'tuesday' => 'selasa', 'wednesday' => 'rabu', 'thursday' => 'kamis', 'friday' => 'jumat', 'saturday' => 'sabtu', 'sunday' => 'minggu', 'senin' => 'senin', 'selasa' => 'selasa', 'rabu' => 'rabu', 'kamis' => 'kamis', 'jumat' => 'jumat', 'sabtu' => 'sabtu', 'minggu' => 'minggu'];
        $targetDay = $dayMap[strtolower($hariInput)] ?? strtolower($hariInput);

        $jadwal = \App\Models\JadwalJaga::with('warga')->where('hari', $targetDay)->get();
        $senderPhone = $this->normalizePhone($chatId);
        $sender = Warga::where('no_hp', $senderPhone)->first();
        $senderName = $sender ? ($sender->panggilan ?? $sender->nama) : "Lur";

        if ($jadwal->isEmpty()) {
            $this->wa->sendMessage($chatId, "📅 Mboten wonten jadwal dinten niku, Pak {$senderName}.");
            return;
        }

        $p1 = $jadwal[0]->warga->nama ?? '?';
        $p2 = $jadwal[1]->warga->nama ?? '?';
        $msg = "Dinten *{$targetDay}* sing piket *{$p1}* kalih *{$p2}* nggih Pak {$senderName}.";
        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleBroadcast($chatId, $senderId, $analysis)
    {
        $senderPhone = $this->normalizePhone($senderId);
        if (!Admin::where('phone', $senderPhone)->exists() && $senderPhone !== '6285326483431') {
            $this->wa->sendMessage($chatId, "⛔ Admin kemawon sing saged.");
            return;
        }
        $message = $analysis['message'] ?? '';
        $wargas = Warga::whereNotNull('no_hp')->get();
        foreach ($wargas as $warga) {
            $phone = $this->normalizePhone($warga->no_hp);
            $this->wa->sendMessage($phone . '@s.whatsapp.net', "📢 *PENGUMUMAN*\n\n" . $message);
            usleep(200000);
        }
        $this->wa->sendMessage($chatId, "✅ Terkirim!");
    }

    protected function findWargaFuzzy($name)
    {
        $name = strtolower(trim($name));
        $warga = Warga::whereRaw('LOWER(panggilan) = ?', [$name])
            ->orWhere('nama', 'like', "%{$name}%")
            ->orWhere('nomor_rumah', $name)
            ->first();

        if ($warga)
            return $warga;

        if (strlen($name) < 4)
            return null;

        $allWarga = Warga::all();
        $closest = null;
        $shortest = 100;

        foreach ($allWarga as $w) {
            $cand = strtolower($w->panggilan ?? $w->nama);
            $lev = levenshtein($name, $cand);
            if ($lev <= 3 && $lev < $shortest) {
                $closest = $w;
                $shortest = $lev;
            }
        }
        return $closest;
    }
}
