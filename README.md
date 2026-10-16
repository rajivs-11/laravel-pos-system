# Gutlicious POS — Complete Laravel Restaurant System

A high-performance Restaurant Point-of-Sale (POS) and Management System built with Laravel 11, MySQL, Blade, and Bootstrap 5.

## Key Features
- **High-Speed Full-Screen POS:** Clean, zero-sidebar cashier workspace, instant menu search, Clean/Cheat/Crave variant customizer, and add-on selection.
- **Multi-Tender Payments:** Cash with live change calculation, UPI QR attestation, Card, and Cash+UPI split payments.
- **Recipe BOM & Automatic Inventory Deduction:** Every sale automatically consumes raw materials (bread slices, paneer grams, sauces, packaging) in an atomic database transaction with pessimistic locking.
- **Kitchen Order Tickets (KOT):** Live order ticket generation with printing and status management.
- **Supplier & Purchase Order Workflow:** Full purchase orders, physical goods receipt incrementing stock, and supplier payment allocations.
- **Day-End Cash Closing:** Daily drawer reconciliation comparing expected system cash with counted physical cash.

## Windows XAMPP Installation
1. Start Apache & MySQL in XAMPP Control Panel.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) and create database: `gutlicious_pos`.
3. In this project folder, copy `.env.example` to `.env`.
4. Run:
   ```cmd
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve --port=8000
   ```
5. Open browser at `http://localhost:8000`.

### Default Credentials
- **Admin:** admin@gutlicious.com / Admin@1234
- **Cashier:** cashier@gutlicious.com / Cashier@1234
=======
# laravel-pos-system
Restaurant POS and Management System built with Laravel 11, MySQL, Blade, and Bootstrap 5.
