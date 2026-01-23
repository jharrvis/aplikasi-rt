<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Warga;
use App\Models\TransaksiJimpitian;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class SendReminder extends Command
{
    protected $signature = 'reminder:send {--target=all}';
    protected $description = 'Send jimpitan reminder to warga who have not paid today';

    public function handle()
    {
        $wa = app(WhatsAppService::class);
        $today = Carbon::today();

        // Get warga who already paid today
        $paidIds = TransaksiJimpitian::whereDate('tanggal', $today)
            ->pluck('warga_id')
            ->toArray();

        // Get warga who have NOT paid
        $unpaid = Warga::whereNotIn('id', $paidIds)
            ->whereNotNull('no_hp')
            ->get();

        $this->info("Found {$unpaid->count()} warga who haven't paid today.");

        $messages = [
            "🔔 Pengingat Jimpitan!\n\nMonggo Lur, ampun kesupen setor jimpitan dinten niki nggih. Cekap ketik \"Lapor [nama] [nominal]\" wonten grup RT.\n\nMaturnuwun! 🙏",
            "⏰ Waktune setor jimpitan!\n\nPanjenengan dereng laporan niki. Monggo dipun isi, ben RT kita makmur terus! 💪",
            "📢 Pak RT ngendikan: Jimpitan dinten niki dereng mlebet lho!\n\nAyo Lur, ketik \"Lapor\" wonten grup. Maturnuwun sanget! 🎉",
        ];

        $sent = 0;
        foreach ($unpaid as $warga) {
            $phone = $this->normalizePhone($warga->no_hp);
            if (!$phone)
                continue;

            $msg = $messages[array_rand($messages)];

            try {
                $wa->sendMessage($phone, $msg);
                $this->info("✅ Sent to: {$warga->nama} ({$phone})");
                $sent++;
                sleep(2); // Avoid rate limiting
            } catch (\Exception $e) {
                $this->error("❌ Failed: {$warga->nama} - " . $e->getMessage());
            }
        }

        $this->info("Done! Sent $sent reminders.");
    }

    protected function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone ?: null;
    }
}
