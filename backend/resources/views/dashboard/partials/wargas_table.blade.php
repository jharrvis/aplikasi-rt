<div class="overflow-x-auto relative">
    <table class="w-full text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-6 py-4 font-semibold">Nama</th>
                <th class="px-6 py-4 font-semibold">Panggilan</th>
                <th class="px-6 py-4 font-semibold">No HP</th>
                <th class="px-6 py-4 font-semibold">No Rumah</th>
                <th class="px-6 py-4 font-semibold text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse($wargas as $warga)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group">
                    <td class="px-6 py-4 font-medium">{{ $warga->nama }}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm">{{ $warga->panggilan ?? '-' }}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm">{{ $warga->no_hp ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span
                            class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                            {{ $warga->nomor_rumah }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                        <button onclick="editModal({{ $warga }})"
                            class="p-1.5 hover:bg-amber-100 text-slate-400 hover:text-amber-600 rounded-lg transition"
                            title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button
                            onclick="deleteModal('{{ route('dashboard.wargas.destroy', $warga->id) }}', '{{ $warga->nama }}')"
                            class="p-1.5 hover:bg-red-100 text-slate-400 hover:text-red-600 rounded-lg transition"
                            title="Hapus">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-2 opacity-50" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p>Tidak ada data warga ditemukan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t border-slate-200 dark:border-slate-700">
    {{ $wargas->links() }}
</div>