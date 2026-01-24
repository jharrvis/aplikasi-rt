<div class="overflow-x-auto relative">
    <table class="w-full text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-6 py-4 font-semibold">Tanggal</th>
                <th class="px-6 py-4 font-semibold">Nama Warga</th>
                <th class="px-6 py-4 font-semibold">Nominal</th>
                <th class="px-6 py-4 font-semibold">Keterangan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse($transactions as $t)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4 text-sm text-slate-500">
                        {{ \Carbon\Carbon::parse($t->tanggal)->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 font-medium">
                        {{ $t->warga?->nama ?? '-' }}
                    </td>
                    <td class="px-6 py-4">
                        <span
                            class="px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                            Rp {{ number_format($t->nominal) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">
                        {{ $t->keterangan ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="p-8 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-2 opacity-50" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p>Tidak ada data transaksi ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t border-slate-200 dark:border-slate-700">
    {{ $transactions->links() }}
</div>