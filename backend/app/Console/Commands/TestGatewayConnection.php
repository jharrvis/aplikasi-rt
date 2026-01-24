<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGatewayConnection extends Command
{
    protected $signature = 'wa:check';
    protected $description = 'Test connection to WA Gateway';

    public function handle()
    {
        $url = env('WA_GATEWAY_URL', 'http://127.0.0.1:3000') . '/status';
        $this->info("Testing connection to: $url");

        try {
            $response = Http::timeout(5)->get($url);
            $this->info("Status Code: " . $response->status());
            $this->info("Body: " . $response->body());
        } catch (\Exception $e) {
            $this->error("Connection Failed: " . $e->getMessage());
        }
    }
}
