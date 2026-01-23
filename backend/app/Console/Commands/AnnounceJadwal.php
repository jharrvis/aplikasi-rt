<?php

namespace App\Console\Commands;

use App\Models\JadwalJaga;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class AnnounceJadwal extends Command
{
    protected $signature = 'jadwal:announce {--group= : Target group JID}';
    protected $description = 'Announce today\'s ronda schedule';

    public function handle(WhatsAppService $wa)
    {
        $hari = Carbon::now()->locale('id')->dayName;
        $jadwal = JadwalJaga::with('warga')->where('hari', $hari)->get();

        if ($jadwal->isEmpty()) {
            $this->info("Tidak ada jadwal jaga untuk hari {$hari}");
            return 0;
        }

        $messages = [
            "🌙 *JADWAL RONDA MALAM INI* 🌙\n\n",
            "🔦 *GILIRAN JAGA MALEM* 🔦\n\n",
            "🏠 *PENGUMUMAN JADWAL RONDA* 🏠\n\n",
        ];

        $msg = $messages[array_rand($messages)];
        $msg .= "📅 *{$hari}*\n\n";

        foreach ($jadwal as $j) {
            $icon = $j->jenis_tugas === 'ronda' ? '🚶' : '🔐';
            $msg .= "{$icon} {$j->warga->nama} ({$j->jenis_tugas})\n";
        }

        $closings = [
            "\n_Monggo sing piket, ampun ketileman nggih!_ 😴",
            "\n_Ojo lali kunci garasi lan pintu pager!_ 🔒",
            "\n_Sugeng malem, ati-ati nggih!_ 🙏",
        ];

        $msg .= $closings[array_rand($closings)];

        $groupJid = $this->option('group') ?? env('RT_GROUP_JID');

        if ($groupJid) {
            $wa->sendMessage($groupJid, $msg);
            $this->info("✅ Jadwal announced to group");
        } else {
            $this->warn("No group JID specified. Use --group or set RT_GROUP_JID in .env");
            $this->line($msg);
        }

        return 0;
    }
}
