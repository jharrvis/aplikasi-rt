<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WargaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rumahData = [
            ['nomor' => 1, 'nama' => "Pak Joko Ir"],
            ['nomor' => 2, 'nama' => "Mbak Ninik"],
            ['nomor' => 3, 'nama' => "Pak Bagyo"],
            ['nomor' => 4, 'nama' => "Bu Endang"],
            ['nomor' => 5, 'nama' => "Pak Kris"],
            ['nomor' => 6, 'nama' => "Bu Trimo"],
            ['nomor' => 7, 'nama' => "Pak Paulus"],
            ['nomor' => 8, 'nama' => "Pak Gatut"],
            ['nomor' => 9, 'nama' => "Bu Tutik"],
            ['nomor' => 10, 'nama' => "Pak Indra"],
            ['nomor' => 11, 'nama' => "Pak Giyono"],
            ['nomor' => 12, 'nama' => "Pak Pratomo"],
            ['nomor' => 13, 'nama' => "Pak Tri"],
            ['nomor' => 14, 'nama' => "Pak Julian"],
            ['nomor' => 15, 'nama' => "Pak Trisno"],
            ['nomor' => 16, 'nama' => "Pak Maxy"],
            ['nomor' => 17, 'nama' => "Bu Bekhan"],
            ['nomor' => 18, 'nama' => "Pak Whindi"],
            ['nomor' => 19, 'nama' => "Mbak Della"],
            ['nomor' => 20, 'nama' => "Mas Rizal"],
            ['nomor' => 21, 'nama' => "Pak Luther"],
        ];

        foreach ($rumahData as $data) {
            DB::table('wargas')->insert([
                'nama' => $data['nama'],
                'nomor_rumah' => $data['nomor'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
