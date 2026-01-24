@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700">
            <div
                class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-sm text-slate-500 font-medium">Total Warga</p>
            <p class="text-2xl font-bold mt-1">{{ $stats['total_warga'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700">
            <div
                class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-xl flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm text-slate-500 font-medium">Masuk Hari Ini</p>
            <p class="text-2xl font-bold mt-1">Rp {{ number_format($stats['today_total'] ?? 0) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700">
            <div
                class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-xl flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm text-slate-500 font-medium">Belum Setor</p>
            <p class="text-2xl font-bold mt-1">{{ $stats['unpaid_today'] }} KK</p>
        </div>
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700">
            <div
                class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 text-purple-600 rounded-xl flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <p class="text-sm text-slate-500 font-medium">Total Bulan Ini</p>
            <p class="text-2xl font-bold mt-1">Rp {{ number_format($stats['month_total'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Analytics & Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Chart (Static Demo for now) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg">Statistik Pemasukan</h3>
            </div>
            <div class="h-[300px] w-full">
                <canvas id="mainChart"></canvas>
            </div>
        </div>

        <!-- Top Contributors (Replaced Doughnut) -->
        <div
            class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <h3 class="font-bold text-lg mb-6">🏆 Top Contributors</h3>
            <div class="space-y-4">
                @forelse($stats['top_contributors'] as $idx => $tc)
                    <div
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $idx === 0 ? 'bg-yellow-100 text-yellow-700' : ($idx === 1 ? 'bg-slate-100 text-slate-700' : 'bg-orange-100 text-orange-700') }}">
                                {{ $idx + 1 }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ $tc->warga->nama ?? 'Hamba Allah' }}</p>
                                <p class="text-xs text-slate-500">{{ $tc->warga->nomor_rumah ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="font-bold text-sm text-emerald-600">Rp {{ number_format($tc->total / 1000) }}k</span>
                    </div>
                @empty
                    <p class="text-center text-slate-500 text-sm">Belum ada data.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Two Column: Recent & Jadwal -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Activity -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="font-bold text-lg">📝 Transaksi Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Nama</th>
                            <th class="px-6 py-4 font-semibold">Tgl</th>
                            <th class="px-6 py-4 font-semibold">Jml</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($recentTransactions as $t)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-sm">{{ $t->warga->nama ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    {{ \Carbon\Carbon::parse($t->tanggal)->format('d M') }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                                        Rp {{ number_format($t->nominal) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-slate-500">Belum ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Jadwal Jaga -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="font-bold text-lg">🌙 Jadwal Jaga Hari Ini</h3>
                <span
                    class="text-xs font-semibold px-2 py-1 bg-primary-100 text-primary-700 rounded-lg">{{ now()->locale('id')->dayName }}</span>
            </div>
            <div class="p-4 space-y-3">
                @forelse($todayJadwal as $j)
                    <div
                        class="flex items-center gap-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700">
                        <div
                            class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg">
                            {{ $j->jenis_tugas === 'ronda' ? '🔦' : '🔐' }}
                        </div>
                        <div>
                            <p class="font-semibold text-sm">{{ $j->warga->nama }}</p>
                            <p class="text-xs text-slate-500 capitalize">{{ $j->jenis_tugas }}</p>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-2 opacity-50" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <p class="text-sm">Tidak ada jadwal jaga malam ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        // Initialize Charts (Static for now, dynamic API data can be added later)
        window.onload = function () {
            const ctxMain = document.getElementById('mainChart').getContext('2d');
            new Chart(ctxMain, {
                type: 'line',
                data: {
                    labels: ['Agu', 'Sep', 'Okt', 'Nov', 'Des', 'Jan'],
                    datasets: [{
                        label: 'Pemasukan (Juta Rp)',
                        data: [8, 12, 10, 15, 13, 18], // Placeholder data
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        };
    </script>
@endsection