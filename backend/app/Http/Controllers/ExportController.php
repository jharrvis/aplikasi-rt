<?php

namespace App\Http\Controllers;

use App\Models\TransaksiJimpitian;
use App\Models\Warga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ExportController extends Controller
{
    public function rekap(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $format = $request->get('format', 'csv');

        $query = TransaksiJimpitian::with('warga');
        $filename = "rekap_jimpitan";

        if ($period === 'daily') {
            $query->whereDate('tanggal', now());
            $filename .= "_" . now()->format('Y-m-d');
        } else {
            $query->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year);
            $filename .= "_" . now()->format('Y-m');
        }

        $data = $query->orderBy('tanggal', 'desc')->get();

        if ($format === 'csv') {
            return $this->exportCsv($data, $filename);
        }

        // Default: return JSON
        return response()->json([
            'period' => $period,
            'total' => $data->sum('nominal'),
            'count' => $data->count(),
            'data' => $data->map(fn($t) => [
                'tanggal' => $t->tanggal,
                'nama' => $t->warga->nama ?? 'Unknown',
                'nominal' => $t->nominal,
            ])
        ]);
    }

    protected function exportCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No', 'Tanggal', 'Nama', 'Nominal', 'Keterangan']);

            foreach ($data as $idx => $t) {
                fputcsv($file, [
                    $idx + 1,
                    $t->tanggal,
                    $t->warga->nama ?? 'Unknown',
                    $t->nominal,
                    $t->keterangan,
                ]);
            }

            // Add total row
            fputcsv($file, ['', '', 'TOTAL', $data->sum('nominal'), '']);
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function unpaidList(Request $request)
    {
        $paidIds = TransaksiJimpitian::whereDate('tanggal', now())
            ->pluck('warga_id')
            ->toArray();

        $unpaid = Warga::whereNotIn('id', $paidIds)->get();

        return response()->json([
            'date' => now()->format('Y-m-d'),
            'unpaid_count' => $unpaid->count(),
            'list' => $unpaid->map(fn($w) => [
                'id' => $w->id,
                'nama' => $w->nama,
                'nomor_rumah' => $w->nomor_rumah,
            ])
        ]);
    }
}
