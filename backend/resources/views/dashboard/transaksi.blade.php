@extends('layouts.dashboard')

@section('title', 'Transaksi Jimpitan')

@section('content')
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div
            class="p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
            <h3 class="font-bold text-lg">💰 Transaksi Jimpitan</h3>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <a href="/export/rekap?period=monthly"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition flex items-center gap-2 shadow-lg shadow-emerald-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
            <form id="filterForm" class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="w-full sm:w-auto">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Bulan</label>
                    <input type="month" name="month" id="filterMonth" value="{{ request('month', now()->format('Y-m')) }}"
                        class="w-full sm:w-48 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                </div>
                <div class="w-full sm:w-auto flex-1 max-w-md">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Warga</label>
                    <select name="warga_id" id="filterWarga"
                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                        <option value="">Semua Warga</option>
                        @foreach($wargas as $w)
                            <option value="{{ $w->id }}" {{ request('warga_id') == $w->id ? 'selected' : '' }}>{{ $w->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-auto flex gap-2">
                    <button type="button" onclick="resetFilters()"
                        class="px-4 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition text-sm font-medium">
                        Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Container for AJAX -->
        <div id="tableContainer">
            @include('dashboard.partials.transaksi_table')
        </div>
    </div>

    <script>
        const filterForm = document.getElementById('filterForm');
        const filterMonth = document.getElementById('filterMonth');
        const filterWarga = document.getElementById('filterWarga');
        const tableContainer = document.getElementById('tableContainer');

        function fetchTransactions() {
            const url = new URL(window.location.href);

            // Update URL params
            if (filterMonth.value) url.searchParams.set('month', filterMonth.value);
            else url.searchParams.delete('month');

            if (filterWarga.value) url.searchParams.set('warga_id', filterWarga.value);
            else url.searchParams.delete('warga_id');

            url.searchParams.delete('page'); // Reset pagination

            window.history.pushState({}, '', url);

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                })
                .catch(error => console.error('Error:', error));
        }

        // Live Filter Events
        filterMonth.addEventListener('change', fetchTransactions);
        filterWarga.addEventListener('change', fetchTransactions);

        function resetFilters() {
            filterMonth.value = "{{ now()->format('Y-m') }}";
            filterWarga.value = "";
            fetchTransactions();
        }

        // Handle Pagination clicks to use AJAX
        document.addEventListener('click', function (e) {
            if (e.target.closest('.pagination a')) {
                e.preventDefault();
                const url = e.target.closest('a').href;
                window.history.pushState({}, '', url);

                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
            }
        });
    </script>
@endsection