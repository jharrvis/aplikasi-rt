<?php

namespace App\Http\Controllers;

use App\Models\Warga;
use App\Models\Admin;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    protected $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:5',
            'target' => 'in:all,selected',
            'warga_ids' => 'array',
        ]);

        $message = "📢 *PENGUMUMAN RT 03*\n\n" . $request->message;
        $message .= "\n\n_Salam, Admin RT 03 Argamas Timur_";

        if ($request->target === 'selected' && !empty($request->warga_ids)) {
            $wargas = Warga::whereIn('id', $request->warga_ids)->whereNotNull('no_hp')->get();
        } else {
            $wargas = Warga::whereNotNull('no_hp')->get();
        }

        $sent = 0;
        $failed = 0;

        foreach ($wargas as $warga) {
            try {
                $phone = $this->formatPhone($warga->no_hp);
                $this->wa->sendMessage($phone . '@s.whatsapp.net', $message);
                $sent++;
                usleep(500000); // 500ms delay between messages
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $wargas->count(),
        ]);
    }

    protected function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }
}
