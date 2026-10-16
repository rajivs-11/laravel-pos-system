<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // POS Workspace (Zero-Distraction Billing)
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/products', [PosController::class, 'searchProducts'])->name('products');
        Route::get('/customer/lookup', [PosController::class, 'lookupCustomer'])->name('customer.lookup');
        Route::post('/customer/quick-create', [PosController::class, 'quickCreateCustomer'])->name('customer.create');
        Route::post('/calculate', [PosController::class, 'calculateTotals'])->name('calculate');
        Route::post('/checkout', [PosController::class, 'finalizeOrder'])->name('checkout');
        Route::post('/hold', [PosController::class, 'holdOrder'])->name('hold');
        Route::get('/held-orders', [PosController::class, 'getHeldOrders'])->name('held.list');
        Route::post('/held-orders/{id}/resume', [PosController::class, 'resumeOrder'])->name('held.resume');
        Route::delete('/held-orders/{id}', [PosController::class, 'cancelHeldOrder'])->name('held.cancel');
        Route::get('/receipt/{id}', [PosController::class, 'printReceipt'])->name('receipt');
        Route::get('/kot/{id}', [PosController::class, 'printKot'])->name('kot');
    });

    // Invoices and Sales Management
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::post('invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

    // Menu and Products
    Route::resource('products', ProductController::class);
    Route::post('products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle');

    // Raw Materials & Recipes
    Route::resource('inventory', InventoryController::class);
    Route::post('inventory/{id}/adjust', [InventoryController::class, 'recordAdjustment'])->name('inventory.adjust');
    Route::resource('recipes', RecipeController::class);

    // Suppliers & Purchases
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::post('purchases/{id}/receive', [PurchaseController::class, 'receiveGoods'])->name('purchases.receive');
    Route::post('purchases/{id}/pay', [PurchaseController::class, 'recordPayment'])->name('purchases.pay');

    // Customers
    Route::resource('customers', CustomerController::class);

    // Reports & Day-End Cash Closing
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/gst-b2c', [ReportController::class, 'gstSummary'])->name('gst');
        Route::get('/cash-closing', [ReportController::class, 'cashClosing'])->name('cash.closing');
        Route::post('/cash-closing', [ReportController::class, 'saveCashClosing'])->name('cash.closing.save');
    });

    // Business Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});
