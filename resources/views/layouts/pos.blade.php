<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gutlicious POS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .cursor-pointer { cursor: pointer; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important; }
        .cat-btn.active { background-color: #3B1E54 !important; color: #fff !important; border-color: #3B1E54 !important; }
        .payment-choice.active { background-color: #3B1E54 !important; color: #fff !important; }
    </style>
</head>
<body class="bg-light">
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
