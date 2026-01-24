<?php

namespace App\Http\Controllers;

use App\Models\Warga;
use App\Models\TransaksiJimpitian;
use App\Models\JadwalJaga;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getStats();
        $recentTransactions = TransaksiJimpitian::with('warga')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $todayJadwal = JadwalJaga::with('warga')
            ->where('hari', Carbon::now()->locale('id')->dayName)
            ->get();

        return view('dashboard.index', compact('stats', 'recentTransactions', 'todayJadwal'));
    }

    public function getStats()
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        return [
            'total_warga' => Warga::count(),
            'today_total' => TransaksiJimpitian::whereDate('tanggal', $today)->sum('nominal'),
            'today_count' => TransaksiJimpitian::whereDate('tanggal', $today)->count(),
            'month_total' => TransaksiJimpitian::where('tanggal', '>=', $monthStart)->sum('nominal'),
            'month_count' => TransaksiJimpitian::where('tanggal', '>=', $monthStart)->count(),
            'unpaid_today' => Warga::count() - TransaksiJimpitian::whereDate('tanggal', $today)->distinct('warga_id')->count('warga_id'),
            'top_contributors' => TransaksiJimpitian::selectRaw('warga_id, SUM(nominal) as total')
                ->whereMonth('tanggal', now()->month)
                ->groupBy('warga_id')
                ->orderByDesc('total')
                ->limit(5)
                ->with('warga')
                ->get(),
        ];
    }

    public function wargas()
    {
        $wargas = Warga::orderBy('nama')->paginate(20);
        return view('dashboard.wargas', compact('wargas'));
    }

    public function transaksi(Request $request)
    {
        $query = TransaksiJimpitian::with('warga');

        if ($request->filled('month')) {
            $date = Carbon::parse($request->month);
            $query->whereMonth('tanggal', $date->month)->whereYear('tanggal', $date->year);
        }

        if ($request->filled('warga_id')) {
            $query->where('warga_id', $request->warga_id);
        }

        $transactions = $query->orderBy('tanggal', 'desc')->paginate(25);
        $wargas = Warga::orderBy('nama')->get();

        return view('dashboard.transaksi', compact('transactions', 'wargas'));
    }

    public function whatsapp()
    {
        return view('dashboard.whatsapp');
    }

    public function apiStats()
    {
        return response()->json($this->getStats());
    }
}
