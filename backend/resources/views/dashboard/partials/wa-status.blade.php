<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">WhatsApp Gateway Status</h2>
        <span id="wa-connection-badge"
            class="px-2 py-1 rounded text-sm font-bold bg-gray-200 text-gray-600">Loading...</span>
    </div>

    <div id="wa-content" class="flex flex-col items-center">
        <!-- Status Text -->
        <p id="wa-status-text" class="text-gray-600 mb-4">Checking connection...</p>

        <!-- QR Code Container -->
        <div id="wa-qr-container" class="hidden mb-4 p-4 border rounded bg-gray-50">
            <img id="wa-qr-image" src="" alt="Scan QR Code" class="w-48 h-48">
            <p class="text-xs text-center text-gray-500 mt-2">Scan dengan WhatsApp di HP Anda</p>
        </div>

        <!-- Actions -->
        <button id="wa-logout-btn"
            class="hidden bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition">
            Disconnect / Logout
        </button>
        <button id="wa-reload-btn"
            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">
            Check Status
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusBadge = document.getElementById('wa-connection-badge');
        const statusText = document.getElementById('wa-status-text');
        const qrContainer = document.getElementById('wa-qr-container');
        const qrImage = document.getElementById('wa-qr-image');
        const logoutBtn = document.getElementById('wa-logout-btn');
        const reloadBtn = document.getElementById('wa-reload-btn');

        function updateUI(status) {
            statusBadge.className = 'px-2 py-1 rounded text-sm font-bold';

            if (status === 'connected') {
                statusBadge.classList.add('bg-green-100', 'text-green-800');
                statusBadge.textContent = 'CONNECTED';
                statusText.textContent = 'WhatsApp Gateway is connected and ready.';
                qrContainer.classList.add('hidden');
                logoutBtn.classList.remove('hidden');
            } else if (status === 'qr_ready') {
                statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                statusBadge.textContent = 'SCAN QR';
                statusText.textContent = 'Please scan the QR code to connect.';
                logoutBtn.classList.add('hidden');
                fetchQr();
            } else {
                statusBadge.classList.add('bg-red-100', 'text-red-800');
                statusBadge.textContent = 'DISCONNECTED';
                statusText.textContent = 'Gateway is disconnected or starting up.';
                qrContainer.classList.add('hidden');
                logoutBtn.classList.add('hidden');
            }
        }

        async function checkStatus() {
            try {
                const response = await fetch('/admin/wa/status');
                const data = await response.json();
                updateUI(data.status);
            } catch (error) {
                console.error('Error fetching WA status:', error);
                updateUI('error');
            }
        }

        async function fetchQr() {
            try {
                const response = await fetch('/admin/wa/qr');
                const data = await response.json();
                if (data.qr) {
                    qrImage.src = data.qr;
                    qrContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error fetching QR:', error);
            }
        }

        async function logout() {
            if (!confirm('Are you sure you want to disconnect?')) return;
            try {
                await fetch('/admin/wa/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                setTimeout(checkStatus, 1000);
            } catch (error) {
                alert('Logout failed');
            }
        }

        reloadBtn.addEventListener('click', checkStatus);
        logoutBtn.addEventListener('click', logout);

        // Initial check
        checkStatus();

        // Poll every 5 seconds if not connected
        setInterval(() => {
            if (statusBadge.textContent !== 'CONNECTED') {
                checkStatus();
            }
        }, 5000);
    });
</script>