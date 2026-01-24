@extends('layouts.dashboard')

@section('title', 'Data Warga')

@section('content')
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div
            class="p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
            <h3 class="font-bold text-lg">Daftar Warga</h3>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="{{ route('dashboard.wargas.index') }}" method="GET" class="relative flex-1 sm:w-64">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / rumah..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 absolute left-3 top-2.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </form>
                <button onclick="openModal()"
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Tambah</span>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <div class="overflow-x-auto">
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
                                    class="p-1.5 hover:bg-amber-100 text-slate-400 hover:text-amber-600 rounded-lg transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <form action="{{ route('dashboard.wargas.destroy', $warga->id) }}" method="POST"
                                    onsubmit="return confirm('Apakah Anda yakin ingin mengarsipkan warga ini? Data masih bisa dipulihkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 hover:bg-red-100 text-slate-400 hover:text-red-600 rounded-lg transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500">
                                Tidak ada data warga ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $wargas->links() }}
        </div>
    </div>

    <!-- Modal -->
    <div id="wargaModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-xl font-bold" id="modalTitle">Tambah Warga</h3>
                    <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l18 18" />
                        </svg>
                    </button>
                </div>

                <form id="wargaForm" method="POST" action="{{ route('dashboard.wargas.store') }}" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" name="id" id="wargaId">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama
                            Lengkap</label>
                        <input type="text" name="nama" id="nama" required
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Panggilan</label>
                            <input type="text" name="panggilan" id="panggilan"
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor
                                Rumah</label>
                            <input type="text" name="nomor_rumah" id="nomor_rumah" required placeholder="Contoh: A1"
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor HP
                            (WhatsApp)</label>
                        <input type="text" name="no_hp" id="no_hp" placeholder="0812..."
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none">
                        <p class="text-xs text-slate-500 mt-1">Format: 08xx atau 62xx</p>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold shadow-lg shadow-primary-500/30 transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('wargaModal');
        const form = document.getElementById('wargaForm');
        const modalTitle = document.getElementById('modalTitle');
        const formMethod = document.getElementById('formMethod');
        const baseUrl = "{{ route('dashboard.wargas.index') }}";

        function openModal() {
            modal.classList.remove('hidden');
            form.reset();
            form.action = "{{ route('dashboard.wargas.store') }}";
            formMethod.value = "POST";
            modalTitle.textContent = "Tambah Warga";
        }

        function editModal(warga) {
            modal.classList.remove('hidden');
            form.action = `${baseUrl}/${warga.id}`;
            formMethod.value = "PUT";
            modalTitle.textContent = "Edit Warga";

            document.getElementById('nama').value = warga.nama;
            document.getElementById('panggilan').value = warga.panggilan || '';
            document.getElementById('nomor_rumah').value = warga.nomor_rumah;
            document.getElementById('no_hp').value = warga.no_hp || '';
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
    </script>
@endsection