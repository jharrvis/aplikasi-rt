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
            ['nomor' => 1, 'nama' => "Pak Joko Ir", 'phone' => '628123456781'],
            ['nomor' => 2, 'nama' => "Mbak Ninik", 'phone' => '628123456782'],
            ['nomor' => 3, 'nama' => "Pak Bagyo", 'phone' => '628123456783'],
            ['nomor' => 4, 'nama' => "Bu Endang", 'phone' => '628123456784'],
            ['nomor' => 5, 'nama' => "Pak Kris", 'phone' => '628123456785'],
            ['nomor' => 6, 'nama' => "Bu Trimo", 'phone' => '628123456786'],
            ['nomor' => 7, 'nama' => "Pak Paulus", 'phone' => '628987654321'],
            ['nomor' => 8, 'nama' => "Pak Gatut", 'phone' => '628123456788'],
            ['nomor' => 9, 'nama' => "Bu Tutik", 'phone' => '628123456789'],
            ['nomor' => 10, 'nama' => "Pak Indra", 'phone' => '628123456710'],
            ['nomor' => 11, 'nama' => "Pak Giyono", 'phone' => '628123456711'],
            ['nomor' => 12, 'nama' => "Pak Pratomo", 'phone' => '628123456712'],
            ['nomor' => 13, 'nama' => "Pak Tri", 'phone' => '628111222333'],
            ['nomor' => 14, 'nama' => "Pak Julian", 'phone' => '6285326483431'],
            ['nomor' => 15, 'nama' => "Pak Trisno", 'phone' => '628123456715'],
            ['nomor' => 16, 'nama' => "Pak Maxy", 'phone' => '628123456716'],
            ['nomor' => 17, 'nama' => "Bu Bekhan", 'phone' => '628123456717'],
            ['nomor' => 18, 'nama' => "Pak Whindi", 'phone' => '628123456718'],
            ['nomor' => 19, 'nama' => "Mbak Della", 'phone' => '628123456719'],
            ['nomor' => 20, 'nama' => "Mas Rizal", 'phone' => '628123456720'],
            ['nomor' => 21, 'nama' => "Pak Luther", 'phone' => '628123456789'],
        ];

        foreach ($rumahData as $data) {
            DB::table('wargas')->insert([
                'nama' => $data['nama'],
                'nomor_rumah' => $data['nomor'],
                'no_hp' => $data['phone'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
