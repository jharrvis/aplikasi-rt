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

        // DEEP LOGGING: See exactly what the gateway is sending
        Log::info("--- Webhook Request Received ---");
        // Log keys only to avoid flooding, or full data if it's not too big
        if (isset($data['messages'][0]['message'])) {
            Log::info("Message Body Keys: " . implode(', ', array_keys($data['messages'][0]['message'])));
        }

        $msg = null;
        $chatId = null;
        $senderId = null;
        $imageUrl = null;
        $imageBase64 = null;

        // Parse Message (Baileys Adapter)
        if (isset($data['messages'][0])) {
            $m = $data['messages'][0];
            if (!($m['key']['fromMe'] ?? false)) {
                $chatId = $m['key']['remoteJid'] ?? null;
                $senderId = $m['key']['participant'] ?? $chatId;

                // Unwrap message if nested
                $content = $m['message'] ?? [];

                // Deep inspection for nested types
                if (isset($content['ephemeralMessage'])) {
                    Log::info("Unwrapping Ephermal Message");
                    $content = $content['ephemeralMessage']['message'] ?? $content;
                }
                if (isset($content['viewOnceMessage'])) {
                    Log::info("Unwrapping ViewOnce Message");
                    $content = $content['viewOnceMessage']['message'] ?? $content;
                }
                if (isset($content['viewOnceMessageV2'])) {
                    Log::info("Unwrapping ViewOnceV2 Message");
                    $content = $content['viewOnceMessageV2']['message'] ?? $content;
                }

                $msg = $content['conversation'] ?? $content['extendedTextMessage']['text'] ?? null;

                // Image Handling
                if (isset($content['imageMessage'])) {
                    $img = $content['imageMessage'];
                    $msg = $img['caption'] ?? '[IMAGE]';
                    $imageUrl = $img['url'] ?? null;
                    $imageBase64 = $img['base64'] ?? null;

                    if ($imageBase64) {
                        Log::info("Success! Found imageBase64 in payload (" . strlen($imageBase64) . " chars)");
                    } else {
                        Log::warning("Found imageMessage but base64 is MISSING. Keys: " . implode(', ', array_keys($img)));
                    }
                }
            }
        }

        // Fallback for non-baileys or direct images
        if ((!$msg && !$imageUrl && !$imageBase64) || !$chatId)
            return response()->json(['status' => 'ignored']);

        // Identify sender
        $senderPhone = $this->normalizePhone($senderId);
        $knownWarga = Warga::where('no_hp', $senderPhone)->first();
        $senderName = $knownWarga ? ($knownWarga->panggilan ?? $knownWarga->nama) : null;

        // --- Silence Logic ---
        $cacheKey = "bot_muted_" . $chatId;
        if (Cache::has($cacheKey)) {
            $wakeTriggers = ['ngadimin', 'tangi', 'halo min', 'pagi min', 'siang min', 'sore min', 'malam min', 'oy min'];
            $shouldWake = false;
            foreach ($wakeTriggers as $trigger) {
                if (stripos($msg ?? '', $trigger) !== false) {
                    $shouldWake = true;
                    break;
                }
            }
            if (!$shouldWake)
                return response()->json(['status' => 'muted']);
            Cache::forget($cacheKey);
        }

        // AI Analysis
        if ($imageUrl || $imageBase64) {
            $wargaList = Warga::pluck('nama')->implode(", ");
            $analysis = $this->ai->analyzeImage($imageUrl, $wargaList, $imageBase64);
        } else {
            $analysis = $this->ai->analyzeMessage($msg, $senderName);
        }

        Log::info("AI Analysis Result Strategy:", ['type' => $analysis['type'] ?? 'none']);

        if (empty($analysis) || !isset($analysis['type']))
            return response()->json(['status' => 'no_action']);

        // Route Action
        if (($analysis['type'] ?? '') === 'ocr_error') {
            $this->wa->sendMessage($chatId, "⚠️ " . ($analysis['message'] ?? 'Gagal membaca gambar.'));
        } elseif ($analysis['type'] === 'ocr_result' || $analysis['type'] === 'report' || $analysis['type'] === 'correction') {
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
            if (isset($analysis['reply']))
                $this->wa->sendMessage($chatId, $analysis['reply']);
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

        foreach ($analysis['data'] ?? [] as $item) {
            $name = $item['name'] ?? '';
            $amount = intval($item['amount'] ?? 0);
            $warga = $this->findWargaFuzzy($name);

            if ($warga) {
                $existing = TransaksiJimpitian::where('warga_id', $warga->id)->whereDate('tanggal', now())->first();
                if ($existing) {
                    if (!$isCorrection && ($analysis['type'] !== 'ocr_result')) {
                        $reply .= "⚠️ *{$warga->nama}*: Sampun laporan (Rp " . number_format($existing->nominal) . ").\n";
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
                if (!empty($name))
                    $reply .= "❓ *{$name}*: Mboten kepanggih.\n";
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
            $warga = $this->findWargaFuzzy($analysis['name'] ?? '');
            if (!$warga) {
                $this->wa->sendMessage($chatId, "Sinten niku? 😅");
                return;
            }
            $transactions = TransaksiJimpitian::where('warga_id', $warga->id)->orderBy('tanggal', 'desc')->limit(10)->get();
            $total = TransaksiJimpitian::where('warga_id', $warga->id)->sum('nominal');
            $msg = "📊 *Rekap Jimpitan {$warga->nama}*\n🏠 Rumah: {$warga->nomor_rumah}\n\n";
            if ($transactions->isEmpty()) {
                $msg .= "_Dereng wonten catatan._\n";
            } else {
                $msg .= "*10 Transaksi Terakhir:*\n";
                foreach ($transactions as $t) {
                    $date = \Carbon\Carbon::parse($t->tanggal)->format('d M');
                    $msg .= "• {$date}: Rp " . number_format($t->nominal) . "\n";
                }
            }
            $msg .= "\n💰 *Total: Rp " . number_format($total) . "*";
            $this->wa->sendMessage($chatId, $msg);
            return;
        }

        $query = TransaksiJimpitian::selectRaw('warga_id, SUM(nominal) as total_nominal')->with('warga');
        if ($period === 'monthly') {
            $month = $analysis['month'] ?? now()->month;
            $year = $analysis['year'] ?? now()->year;
            $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            $msgTitle = "Wulan " . ucfirst(\Carbon\Carbon::createFromDate($year, $month, 1)->locale('id')->monthName);
        } elseif ($period === 'yesterday') {
            $query->whereDate('tanggal', now()->subDay());
            $msgTitle = "Wingi";
        } else {
            $query->whereDate('tanggal', now());
            $msgTitle = "Dinten Niki";
        }

        $transactions = $query->groupBy('warga_id')->orderByDesc('total_nominal')->get();
        $total = $transactions->sum('total_nominal');
        $msg = "📊 *Rekap Jimpitian $msgTitle*\n\n";
        foreach ($transactions as $idx => $t) {
            if ($t->warga)
                $msg .= ($idx + 1) . ". {$t->warga->nama}: Rp " . number_format($t->total_nominal) . "\n";
        }
        if ($transactions->isEmpty())
            $msg .= "_Dereng wonten setoran._\n";
        $msg .= "\n💰 *Total: Rp " . number_format($total) . "*";
        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleLaporTemplate($chatId)
    {
        $wargas = Warga::orderBy('nomor_rumah')->get();
        $msg = "📝 *Template Lapor Jimpitan*\n_Copy, edit nominal, kirim balik:_\n\n";
        foreach ($wargas as $w) {
            $nama = $w->panggilan ?? $w->nama;
            $msg .= "{$nama} 1000\n";
        }
        $this->wa->sendMessage($chatId, $msg);
    }

    protected function handleQueryStats($chatId, $analysis)
    {
        $warga = $this->findWargaFuzzy($analysis['name'] ?? '');
        if (!$warga) {
            $this->wa->sendMessage($chatId, "Mboten kenal je.. 😅");
            return;
        }
        $total = TransaksiJimpitian::where('warga_id', $warga->id)->sum('nominal');
        $this->wa->sendMessage($chatId, "🔎 *{$warga->nama}*\n💰 Total: Rp " . number_format($total));
    }

    protected function normalizePhone($phone)
    {
        $phone = explode('@', $phone)[0];
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '08'))
            $phone = '62' . substr($phone, 1);
        if (str_starts_with($phone, '8'))
            $phone = '62' . $phone;
        return $phone;
    }

    protected function handleLeaderboard($chatId, $analysis)
    {
        $rankings = TransaksiJimpitian::selectRaw('warga_id, SUM(nominal) as total')->groupBy('warga_id')->orderByDesc('total')->limit(10)->get();
        $msg = "🏆 *Leaderboard Jimpitan*\n\n";
        foreach ($rankings as $idx => $r) {
            $w = Warga::find($r->warga_id);
            if ($w)
                $msg .= ($idx + 1) . ". {$w->nama}: Rp " . number_format($r->total) . "\n";
        }
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
        if ($jadwal->isEmpty()) {
            $this->wa->sendMessage($chatId, "📅 Mboten wonten jadwal dinten niku.");
            return;
        }
        $p1 = $jadwal[0]->warga->nama ?? '?';
        $p2 = $jadwal[1]->warga->nama ?? '?';
        $this->wa->sendMessage($chatId, "Dinten *{$targetDay}* sing piket *{$p1}* kalih *{$p2}*.");
    }

    protected function handleBroadcast($chatId, $senderId, $analysis)
    {
        $senderPhone = $this->normalizePhone($senderId);
        if (!Admin::where('phone', $senderPhone)->exists() && $senderPhone !== '6285326483431') {
            $this->wa->sendMessage($chatId, "⛔ Admin kemawon.");
            return;
        }
        $message = $analysis['message'] ?? '';
        foreach (Warga::whereNotNull('no_hp')->get() as $w) {
            $this->wa->sendMessage($this->normalizePhone($w->no_hp) . '@s.whatsapp.net', "📢 *PENGUMUMAN*\n\n" . $message);
            usleep(200000);
        }
        $this->wa->sendMessage($chatId, "✅ Terkirim!");
    }

    protected function findWargaFuzzy($name)
    {
        $name = strtolower(trim($name));
        $warga = Warga::whereRaw('LOWER(panggilan) = ?', [$name])->orWhere('nama', 'like', "%{$name}%")->orWhere('nomor_rumah', $name)->first();
        if ($warga)
            return $warga;
        if (strlen($name) < 4)
            return null;
        $closest = null;
        $shortest = 100;
        foreach (Warga::all() as $w) {
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
