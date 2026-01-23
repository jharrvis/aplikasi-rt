@extends('layouts.dashboard')

@section('title', 'Daftar Warga')

@section('content')
    <div class="header">
        <h1>👥 Daftar Warga</h1>
        <span style="color: var(--text-secondary);">Total: {{ $wargas->total() }} warga</span>
    </div>

    <div class="card">
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Panggilan</th>
                        <th>No. Rumah</th>
                        <th>No. HP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wargas as $idx => $w)
                        <tr>
                            <td>{{ $wargas->firstItem() + $idx }}</td>
                            <td>{{ $w->nama }}</td>
                            <td>{{ $w->panggilan ?? '-' }}</td>
                            <td>{{ $w->nomor_rumah ?? '-' }}</td>
                            <td>{{ $w->no_hp ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; color:var(--text-secondary)">Tidak ada data warga</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="pagination">
                @if($wargas->previousPageUrl())
                    <a href="{{ $wargas->previousPageUrl() }}">← Prev</a>
                @endif

                <span class="active">{{ $wargas->currentPage() }}</span>

                @if($wargas->nextPageUrl())
                    <a href="{{ $wargas->nextPageUrl() }}">Next →</a>
                @endif
            </div>
        </div>
    </div>
@endsection