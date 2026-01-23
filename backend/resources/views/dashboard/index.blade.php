@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <div class="header">
        <h1>Dashboard RT 03</h1>
        <span style="color: var(--text-secondary);">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Warga</div>
            <div class="value primary">{{ $stats['total_warga'] }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Jimpitan Hari Ini</div>
            <div class="value success">Rp {{ number_format($stats['today_total']) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Belum Setor Hari Ini</div>
            <div class="value warning">{{ $stats['unpaid_today'] }} orang</div>
        </div>
        <div class="stat-card">
            <div class="label">Total Bulan Ini</div>
            <div class="value success">Rp {{ number_format($stats['month_total']) }}</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">🏆 Top Contributors Bulan Ini</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['top_contributors'] as $idx => $tc)
                            <tr>
                                <td>
                                    @if($idx === 0) 🥇
                                    @elseif($idx === 1) 🥈
                                    @elseif($idx === 2) 🥉
                                    @else {{ $idx + 1 }}
                                    @endif
                                </td>
                                <td>{{ $tc->warga->nama ?? '-' }}</td>
                                <td>Rp {{ number_format($tc->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center; color:var(--text-secondary)">Belum ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">🌙 Jadwal Jaga Hari Ini</div>
            <div class="card-body">
                @forelse($todayJadwal as $j)
                    <div
                        style="display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0; border-bottom:1px solid var(--border);">
                        <span>{{ $j->jenis_tugas === 'ronda' ? '🚶' : '🔐' }}</span>
                        <span>{{ $j->warga->nama }}</span>
                        <span class="badge badge-success">{{ ucfirst($j->jenis_tugas) }}</span>
                    </div>
                @empty
                    <p style="color:var(--text-secondary); text-align:center;">Tidak ada jadwal hari ini</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1.5rem;">
        <div class="card-header">📝 Transaksi Terbaru</div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $t)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($t->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $t->warga->nama ?? '-' }}</td>
                            <td>Rp {{ number_format($t->nominal) }}</td>
                            <td><span class="badge badge-success">Lunas</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color:var(--text-secondary)">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection