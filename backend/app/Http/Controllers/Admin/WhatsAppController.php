<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected $waService;

    public function __construct(WhatsAppGatewayService $waService)
    {
        $this->waService = $waService;
    }

    public function getStatus()
    {
        return response()->json($this->waService->getStatus());
    }

    public function getQr()
    {
        return response()->json($this->waService->getQrCode());
    }

    public function logout()
    {
        return response()->json($this->waService->logout());
    }
}
