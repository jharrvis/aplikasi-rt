@extends('layouts.dashboard')

@section('title', 'Data Warga')

@section('content')
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div
            class="p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
            <h3 class="font-bold text-lg">Daftar Warga</h3>

            <div class="flex items-center gap-2 w-full sm:w-auto relative">
                <div class="relative flex-1 sm:w-64">
                    <input type="text" id="searchInput" value="{{ request('search') }}" placeholder="Cari nama / rumah..."
                        class="w-full pl-10 pr-10 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 absolute left-3 top-2.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <!-- Reset Button -->
                    <button id="resetSearch" onclick="resetSearch()"
                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l18 18" />
                        </svg>
                    </button>
                </div>
                <button onclick="openModal()"
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition flex items-center gap-2 shadow-lg shadow-primary-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Tambah</span>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div id="flashMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4 rounded-r relative"
                role="alert">
                <p>{{ session('success') }}</p>
                <button onclick="this.parentElement.remove()" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20">
                        <title>Close</title>
                        <path
                            d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- Table Container for AJAX -->
        <div id="tableContainer">
            @include('dashboard.partials.wargas_table')
        </div>
    </div>

    <!-- Form Modal -->
    <div id="wargaModal" class="fixed inset-0 z-50 hidden transition-opacity duration-300 opacity-0 pointer-events-none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transform transition-all duration-300"
            onclick="closeModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-4 transform transition-all duration-300 scale-95"
            id="modalContent">
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

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama
                            Lengkap</label>
                        <input type="text" name="nama" id="nama" required
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Panggilan</label>
                            <input type="text" name="panggilan" id="panggilan"
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor
                                Rumah</label>
                            <input type="text" name="nomor_rumah" id="nomor_rumah" required placeholder="Contoh: A1"
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor HP
                            (WhatsApp)</label>
                        <input type="text" name="no_hp" id="no_hp" placeholder="0812..."
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-primary-500 outline-none transition-all">
                        <p class="text-xs text-slate-500 mt-1">Format: 08xx atau 62xx</p>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold shadow-lg shadow-primary-500/30 transition transform hover:scale-[1.02]">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Alert Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden transition-opacity duration-300 opacity-0 pointer-events-none">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-4 transform transition-all duration-300 scale-95"
            id="deleteModalContent">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden p-6 text-center">
                <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Konfirmasi Hapus</h3>
                <p class="text-slate-500 text-sm mb-6">Apakah Anda yakin ingin mengarsipkan warga <strong
                        id="deleteTargetName"></strong>? Data ini dapat dipulihkan nanti.</p>

                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="flex gap-3 justify-center">
                        <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/30 transition transform hover:scale-[1.02]">Ya,
                            Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal Logic
        const modal = document.getElementById('wargaModal');
        const modalContent = document.getElementById('modalContent');
        const form = document.getElementById('wargaForm');
        const modalTitle = document.getElementById('modalTitle');
        const formMethod = document.getElementById('formMethod');
        const baseUrl = "{{ route('dashboard.wargas.index') }}";

        function openModal() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);

            form.reset();
            form.action = "{{ route('dashboard.wargas.store') }}";
            formMethod.value = "POST";
            modalTitle.textContent = "Tambah Warga";
        }

        function editModal(warga) {
            openModal();
            form.action = `${baseUrl}/${warga.id}`;
            formMethod.value = "PUT";
            modalTitle.textContent = "Edit Warga";

            document.getElementById('nama').value = warga.nama;
            document.getElementById('panggilan').value = warga.panggilan || '';
            document.getElementById('nomor_rumah').value = warga.nomor_rumah;
            document.getElementById('no_hp').value = warga.no_hp || '';
        }

        function closeModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Delete Modal Logic
        const deleteModalEl = document.getElementById('deleteModal');
        const deleteContent = document.getElementById('deleteModalContent');
        const deleteForm = document.getElementById('deleteForm');
        const deleteTargetName = document.getElementById('deleteTargetName');

        function deleteModal(url, name) {
            deleteModalEl.classList.remove('hidden');
            setTimeout(() => {
                deleteModalEl.classList.remove('opacity-0', 'pointer-events-none');
                deleteContent.classList.remove('scale-95');
                deleteContent.classList.add('scale-100');
            }, 10);

            deleteForm.action = url;
            deleteTargetName.textContent = name;
        }

        function closeDeleteModal() {
            deleteModalEl.classList.add('opacity-0', 'pointer-events-none');
            deleteContent.classList.remove('scale-100');
            deleteContent.classList.add('scale-95');
            setTimeout(() => {
                deleteModalEl.classList.add('hidden');
            }, 300);
        }

        // Live Search Logic
        const searchInput = document.getElementById('searchInput');
        const tableContainer = document.getElementById('tableContainer');
        const resetBtn = document.getElementById('resetSearch');
        let debounceTimer;

        searchInput.addEventListener('input', function () {
            const query = this.value;

            if (query.length > 0) {
                resetBtn.classList.remove('hidden');
            } else {
                resetBtn.classList.add('hidden');
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchWargas(query);
            }, 300); // 300ms debounce
        });

        function resetSearch() {
            searchInput.value = '';
            resetBtn.classList.add('hidden');
            fetchWargas('');
        }

        function fetchWargas(query) {
            const url = new URL(window.location.href);
            if (query) {
                url.searchParams.set('search', query);
            } else {
                url.searchParams.delete('search');
            }
            // Reset to page 1 on search
            url.searchParams.delete('page');

            window.history.pushState({}, '', url);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                })
                .catch(error => console.error('Error fetching data:', error));
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
                    });
            }
        });
    </script>
@endsection