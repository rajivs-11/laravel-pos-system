<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gutlicious Administration')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar { width: 260px; min-height: 100vh; background-color: #3B1E54; }
        .nav-link { color: rgba(255,255,255,0.75); border-radius: 8px; margin-bottom: 4px; }
        .nav-link:hover, .nav-link.active { color: #fff; background-color: rgba(255,255,255,0.12); }
    </style>
</head>
<body class="bg-light d-flex">
    <aside class="sidebar p-3 text-white d-flex flex-column">
        <div class="mb-4 d-flex align-items-center gap-2">
            <span class="fs-4 fw-bold text-warning"><i class="bi bi-egg-fried me-2"></i>GUTLICIOUS</span>
        </div>
        <nav class="nav flex-column flex-grow-1">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a href="{{ route('pos.index') }}" class="nav-link text-warning fw-bold"><i class="bi bi-cart3 me-2"></i>POS Workspace</a>
            <a href="{{ route('invoices.index') }}" class="nav-link"><i class="bi bi-receipt me-2"></i>Sales & Invoices</a>
            <a href="{{ route('products.index') }}" class="nav-link"><i class="bi bi-layers me-2"></i>Menu & Pricing</a>
            <a href="{{ route('inventory.index') }}" class="nav-link"><i class="bi bi-box-seam me-2"></i>Raw Materials</a>
            <a href="{{ route('recipes.index') }}" class="nav-link"><i class="bi bi-clipboard2-data me-2"></i>Recipes / BOM</a>
            <a href="{{ route('purchases.index') }}" class="nav-link"><i class="bi bi-truck me-2"></i>Purchase Orders</a>
            <a href="{{ route('suppliers.index') }}" class="nav-link"><i class="bi bi-building me-2"></i>Suppliers</a>
            <a href="{{ route('customers.index') }}" class="nav-link"><i class="bi bi-people me-2"></i>Loyalty & Customers</a>
            <a href="{{ route('reports.sales') }}" class="nav-link"><i class="bi bi-bar-chart-line me-2"></i>Reports & GST</a>
            <a href="{{ route('settings.index') }}" class="nav-link"><i class="bi bi-gear me-2"></i>Store Settings</a>
        </nav>
        <div class="border-top pt-3">
            <div class="small text-white-50 mb-1">Logged in as:</div>
            <div class="fw-bold">{{ auth()->user()->name ?? 'Administrator' }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </form>
        </div>
    </aside>
    <main class="flex-grow-1 p-4 overflow-auto" style="height: 100vh;">
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
