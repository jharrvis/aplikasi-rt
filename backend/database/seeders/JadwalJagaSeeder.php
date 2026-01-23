<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warga;
use App\Models\JadwalJaga;

class JadwalJagaSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        JadwalJaga::truncate();

        $jadwal = [
            'senin' => ['Joko Irianto', 'Maxy'],
            'selasa' => ['Whindi', 'Bagyo'],
            'rabu' => ['Gatut', 'Paulus'],
            'kamis' => ['Bu Bekhan', 'Sutrisno'],
            'jumat' => ['Julian', 'Giyono'],
            'sabtu' => ['Pak Tri', 'Giyono'],
            'minggu' => ['Pak Luther', 'Kris'],
        ];

        foreach ($jadwal as $hari => $names) {
            foreach ($names as $name) {
                // Find warga by name (fuzzy)
                $warga = Warga::where('nama', 'like', "%{$name}%")
                    ->orWhere('panggilan', 'like', "%{$name}%")
                    ->first();

                if ($warga) {
                    JadwalJaga::create([
                        'hari' => $hari,
                        'warga_id' => $warga->id,
                        'jenis_tugas' => 'Ronda Malam',
                    ]);
                    $this->command->info("Added: {$hari} - {$warga->nama}");
                } else {
                    $this->command->warn("Warga not found: {$name}");
                }
            }
        }
    }
}
