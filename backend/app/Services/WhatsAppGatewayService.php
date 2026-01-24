<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('WA_GATEWAY_URL', 'http://127.0.0.1:3000');
    }

    public function getStatus()
    {
        try {
            $response = Http::timeout(3)->get($this->baseUrl . '/status');
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getQrCode()
    {
        try {
            $response = Http::timeout(3)->get($this->baseUrl . '/qr');
            if ($response->successful()) {
                return $response->json();
            }
            return ['error' => 'Failed to fetch QR from Gateway. Status: ' . $response->status()];
        } catch (\Exception $e) {
            return ['error' => 'Connection to Gateway failed: ' . $e->getMessage()];
        }
    }

    public function sendMessage($number, $message)
    {
        try {
            // Ensure number format (remove leading 0 or +62, then add 62)
            // For simplicity, assuming number is already mostly correct or handled by caller
            // This regex strip non-digits
            $number = preg_replace('/[^0-9]/', '', $number);

            // Basic formatting for ID
            if (str_starts_with($number, '0')) {
                $number = '62' . substr($number, 1);
            }

            $response = Http::post($this->baseUrl . '/send-message', [
                'number' => $number,
                'message' => $message,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WA Gateway Send Error: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function logout()
    {
        try {
            $response = Http::post($this->baseUrl . '/logout');
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error'];
        }
    }
}
