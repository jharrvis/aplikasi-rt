<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $gatewayUrl;

    public function __construct()
    {
        $this->gatewayUrl = env('WA_GATEWAY_URL', 'http://localhost:3000');
    }

    public function sendMessage($number, $message)
    {
        try {
            $response = Http::post($this->gatewayUrl . '/send-message', [
                'number' => $number,
                'message' => $message,
            ]);

            Log::info("Sent WA to $number: " . $response->status());
            return $response->json();
        } catch (\Exception $e) {
            Log::error("Failed to send WA: " . $e->getMessage());
            return null;
        }
    }
}
