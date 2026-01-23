<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - RT 03 Argamas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: #334155;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            padding: 1.5rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .main {
            margin-left: 250px;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .stat-card .label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .stat-card .value.success {
            color: var(--success);
        }

        .stat-card .value.warning {
            color: var(--warning);
        }

        .stat-card .value.primary {
            color: var(--primary);
        }

        .card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
        }

        th {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 1024px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main {
                margin-left: 0;
            }
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-secondary);
            text-decoration: none;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
        }

        .pagination .active {
            background: var(--primary);
            color: white;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="logo">🏠 RT 03</div>
        <nav>
            <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
                📊 Dashboard
            </a>
            <a href="/dashboard/wargas" class="nav-item {{ request()->is('dashboard/wargas*') ? 'active' : '' }}">
                👥 Warga
            </a>
            <a href="/dashboard/transaksi" class="nav-item {{ request()->is('dashboard/transaksi*') ? 'active' : '' }}">
                💰 Transaksi
            </a>
            <a href="/export/rekap" class="nav-item">
                📥 Export CSV
            </a>
        </nav>
    </aside>

    <main class="main">
        @yield('content')
    </main>
</body>

</html>