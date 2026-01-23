@extends('layouts.dashboard')

@section('title', 'Transaksi Jimpitan')

@section('content')
    <div class="header">
        <h1>💰 Transaksi Jimpitan</h1>
        <a href="/export/rekap?period=monthly" class="btn btn-primary">📥 Export CSV</a>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body">
            <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
                <div>
                    <label style="font-size:0.875rem; color:var(--text-secondary);">Bulan</label><br>
                    <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}"
                        style="padding:0.5rem; background:var(--bg-dark); border:1px solid var(--border); border-radius:6px; color:var(--text-primary);">
                </div>
                <div>
                    <label style="font-size:0.875rem; color:var(--text-secondary);">Warga</label><br>
                    <select name="warga_id"
                        style="padding:0.5rem; background:var(--bg-dark); border:1px solid var(--border); border-radius:6px; color:var(--text-primary); min-width:150px;">
                        <option value="">Semua</option>
                        @foreach($wargas as $w)
                            <option value="{{ $w->id }}" {{ request('warga_id') == $w->id ? 'selected' : '' }}>{{ $w->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="align-self:flex-end;">
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Nominal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @forelse($transactions as $t)
                        @php $total += $t->nominal; @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($t->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $t->warga->nama ?? '-' }}</td>
                            <td>Rp {{ number_format($t->nominal) }}</td>
                            <td>{{ $t->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color:var(--text-secondary)">Tidak ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="font-weight:600; background:rgba(99,102,241,0.1);">
                        <td colspan="2">Total</td>
                        <td colspan="2">Rp {{ number_format($total) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="pagination">
                @if($transactions->previousPageUrl())
                    <a href="{{ $transactions->appends(request()->query())->previousPageUrl() }}">← Prev</a>
                @endif

                <span class="active">{{ $transactions->currentPage() }} / {{ $transactions->lastPage() }}</span>

                @if($transactions->nextPageUrl())
                    <a href="{{ $transactions->appends(request()->query())->nextPageUrl() }}">Next →</a>
                @endif
            </div>
        </div>
    </div>
@endsection