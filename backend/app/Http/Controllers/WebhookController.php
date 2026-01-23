<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Services\OpenRouterService;
use App\Models\Warga;
use App\Models\TransaksiJimpitian;
use App\Models\Admin;
use Illuminate\Support\Facades\Log;

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
        $chatId = null;  // Where to reply (Group or PM)
        $senderId = null; // Who sent the message (User Phone/LID)

        // Parse Message (Baileys Adapter)
        if (isset($data['messages'][0])) {
            $m = $data['messages'][0];
            if (!($m['key']['fromMe'] ?? false)) {
                $msg = $m['message']['conversation'] ?? $m['message']['extendedTextMessage']['text'] ?? null;
                $chatId = $m['key']['remoteJid'] ?? null;
                $senderId = $m['key']['participant'] ?? $chatId; // If group, use participant. If PM, use remoteJid.
            }
        } elseif (isset($data['message']) && isset($data['from'])) {
            $msg = $data['message'];
            $chatId = $data['from'];
            $senderId = $data['sender'] ?? $chatId;
        }

        if (!$msg || !$chatId)
            return response()->json(['status' => 'ignored']);

        // Identify sender by phone
        $senderPhone = $this->normalizePhone($senderId);
        $knownWarga = Warga::where('no_hp', $senderPhone)->first();
        $senderName = $knownWarga ? ($knownWarga->panggilan ?? $knownWarga->nama) : null;

        // 1. Analyze with AI (include sender name for personalized response)
        $analysis = $this->ai->analyzeMessage($msg, $senderName);
        Log::info("AI Analysis:", $analysis ?? []);

        if (empty($analysis) || !isset($analysis['type']))
            return response()->json(['status' => 'no_action']);

        // 2. Route Action
        if ($analysis['type'] === 'report' || $analysis['type'] === 'correction') {
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

        // Security: Check if sender is registered warga
        $reporter = Warga::where('no_hp', $senderPhone)->first();

        // Admin check for corrections
        $isAdmin = Admin::where('phone', $senderPhone)->exists() || $senderPhone === '6285326483431';

        // Allowed if admin OR if it's today's data (everyone can correct today's data)
        if ($isCorrection && !$isAdmin) {
            // We'll check the date during processing individual items below
            // This top-level check is now just a bypass for admins
        }

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
                    if (!$isCorrection) {
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
                $reply .= "❓ *{$name}*: Mboten kepanggih, cobi ejaan liyane.\n";
            }
        }

        if ($total > 0)
            $reply .= "\nMaturnuwun! Total sementawis: Rp " . number_format($total) . " 🙏";
        $this->wa->sendMessage($chatId, $reply);
    }

    protected function handleRecap($chatId, $analysis)
    {
        $period = $analysis['period'] ?? 'daily';

        // Handle warga-specific recap
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

        // Group by Warga to sum total per person
        $query = TransaksiJimpitian::selectRaw('warga_id, SUM(nominal) as total_nominal')
            ->with('warga'); // Eager load warga

        $title = "";

        if ($period === 'monthly') {
            $query->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year);
            $title = "Wulan Niki (" . now()->format('F Y') . ")";
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
        $count = $transactions->count(); // Number of unique warga contributing

        $msg = "📊 *Rekap Jimpitian $title*\n\n";

        foreach ($transactions as $idx => $t) {
            $num = $idx + 1;
            // Use total_nominal alias
            $msg .= "$num. {$t->warga->nama}: Rp " . number_format($t->total_nominal) . "\n";
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
        $msg .= "_Ketik 'kosong' jika tidak setor._\n";
        $msg .= "_Contoh: Pak Joko kosong_";

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

        $msg = "🔎 Cek Data *{$warga->nama}* ($periodText):\n";
        $msg .= "💰 Total: Rp " . number_format($total) . "\n";
        $msg .= "Rajin menabung pangkal kaya! 🤑";

        $this->wa->sendMessage($chatId, $msg);
    }

    protected function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
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
            $medal = $medals[$idx] ?? ($idx + 1) . ".";
            $msg .= "$medal {$warga->nama}: Rp " . number_format($r->total) . "\n";
        }

        $msg .= "\n_Maturnuwun sanget kangge sedoyo! Rajin tenan panjenengan!_ 💪";

        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleJadwal($chatId, $analysis)
    {
        $hari = $analysis['hari'] ?? \Carbon\Carbon::now()->locale('id')->dayName;

        $jadwal = \App\Models\JadwalJaga::with('warga')->where('hari', $hari)->get();

        if ($jadwal->isEmpty()) {
            $this->wa->sendMessage($chatId, "📅 Tidak ada jadwal jaga untuk hari *{$hari}*, Lur!");
            return;
        }

        $msg = "🌙 *Jadwal Jaga - {$hari}*\n\n";

        foreach ($jadwal as $j) {
            $icon = $j->jenis_tugas === 'ronda' ? '🚶' : '🔐';
            $msg .= "{$icon} {$j->warga->nama} ({$j->jenis_tugas})\n";
        }

        $msg .= "\n_Monggo sing piket, ati-ati nggih!_ 🙏";

        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleBroadcast($chatId, $senderId, $analysis)
    {
        $senderPhone = $this->normalizePhone($senderId);

        // Only admins can broadcast
        if (!Admin::where('phone', $senderPhone)->exists()) {
            $this->wa->sendMessage($chatId, "⛔ Maaf Lur, broadcast hanya bisa dilakukan oleh Admin RT.");
            return;
        }

        $message = $analysis['message'] ?? '';
        if (empty($message)) {
            $this->wa->sendMessage($chatId, "❓ Pesan broadcast-nya mana, Lur? Format: broadcast: [isi pesan]");
            return;
        }

        $wargas = Warga::whereNotNull('no_hp')->get();
        $sent = 0;

        $broadcastMsg = "📢 *PENGUMUMAN RT 03*\n\n" . $message . "\n\n_Salam, Admin RT_";

        foreach ($wargas as $warga) {
            $phone = $this->normalizePhone($warga->no_hp);
            $this->wa->sendMessage($phone . '@s.whatsapp.net', $broadcastMsg);
            $sent++;
            usleep(300000); // 300ms delay
        }

        $this->wa->sendMessage($chatId, "✅ Broadcast terkirim ke {$sent} warga!");
    }

    protected function findWargaFuzzy($name)
    {
        $name = strtolower(trim($name));

        // 0. EXACT panggilan match (highest priority for short names like "Tri")
        $exactPanggilan = Warga::whereRaw('LOWER(panggilan) = ?', [$name])->first();
        if ($exactPanggilan) {
            Log::info("Exact panggilan match: {$exactPanggilan->nama}");
            return $exactPanggilan;
        }

        // 1. Partial Match (LIKE)
        $warga = Warga::where('nama', 'like', "%{$name}%")
            ->orWhere('panggilan', 'like', "%{$name}%")
            ->orWhere('nomor_rumah', $name)
            ->first();
        if ($warga)
            return $warga;

        // 2. For SHORT inputs (<4 chars), require very strict matching
        if (strlen($name) < 4) {
            Log::warning("Short name '$name' - no exact match found, skipping fuzzy.");
            return null; // Don't risk matching "Tri" to "Trimo"
        }

        // 3. Advanced Fuzzy Match (Levenshtein)
        $allWarga = Warga::all();
        $closest = null;
        $shortest = 100;

        Log::info("Fuzzy Search for: $name");

        foreach ($allWarga as $w) {
            $candidates = [];
            $candidates[] = strtolower($w->nama);
            if ($w->panggilan)
                $candidates[] = strtolower($w->panggilan);

            $words = explode(' ', strtolower($w->nama));
            foreach ($words as $word) {
                if (strlen($word) > 2)
                    $candidates[] = $word;
            }

            foreach ($candidates as $cand) {
                $lev = levenshtein($name, $cand);
                $threshold = (strlen($name) > 4) ? 3 : 2;

                if ($lev <= $threshold && $lev < $shortest) {
                    Log::info("Match candidate: $cand ($lev) for {$w->nama}");
                    $closest = $w;
                    $shortest = $lev;
                }
            }
        }

        if ($closest) {
            Log::info("Selected Fuzzy Match: {$closest->nama} (Distance: $shortest)");
        } else {
            Log::warning("No match found for: $name");
        }

        return $closest;
    }
}
