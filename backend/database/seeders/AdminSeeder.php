<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['phone' => '628123456789', 'name' => 'Pak Luther', 'role' => 'super_admin', 'password' => Hash::make('password')],
            ['phone' => '628987654321', 'name' => 'Pak Paulus', 'role' => 'admin', 'password' => Hash::make('password')],
            ['phone' => '628111222333', 'name' => 'Pak Tri', 'role' => 'admin', 'password' => Hash::make('password')],
            ['phone' => '6285326483431', 'name' => 'Julian', 'role' => 'admin', 'password' => Hash::make('password')],
        ];

        foreach ($admins as $admin) {
            Admin::updateOrCreate(['phone' => $admin['phone']], $admin);
            $this->command->info("Added admin: {$admin['name']}");
        }
    }
}
