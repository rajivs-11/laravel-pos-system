<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\Customer;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller {
    public function index() {
        $today = now()->startOfDay();

        // 1. Sales & Bills aggregates
        $todayInvoices = Invoice::where('created_at', '>=', $today)->where('status', 'paid')->get();
        $grossSales = $todayInvoices->sum('total_amount');
        $netSales = $todayInvoices->sum('taxable_amount');
        $billCount = $todayInvoices->count();
        $avgBill = $billCount > 0 ? round($grossSales / $billCount, 2) : 0;

        // Items sold today
        $itemsSold = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.created_at', '>=', $today)
            ->where('invoices.status', 'paid')
            ->sum('invoice_items.quantity');

        // Payment method breakdown
        $cashSales = InvoicePayment::whereHas('invoice', fn($q) => $q->where('created_at', '>=', $today))
            ->where('payment_method', 'cash')->sum('amount');
        $upiSales = InvoicePayment::whereHas('invoice', fn($q) => $q->where('created_at', '>=', $today))
            ->where('payment_method', 'upi')->sum('amount');
        $cardSales = InvoicePayment::whereHas('invoice', fn($q) => $q->where('created_at', '>=', $today))
            ->where('payment_method', 'card')->sum('amount');

        // Low stock & out of stock
        $lowStockItems = InventoryItem::whereRaw('current_stock <= reorder_level')->where('current_stock', '>', 0)->get();
        $outOfStockItems = InventoryItem::where('current_stock', '<=', 0)->get();

        // Recent Invoices
        $recentInvoices = Invoice::with(['user', 'customer'])->latest()->take(8)->get();

        // Recent Stock Movements
        $recentMovements = StockMovement::with('inventoryItem')->latest()->take(6)->get();

        return view('dashboard.index', compact(
            'grossSales', 'netSales', 'billCount', 'avgBill', 'itemsSold',
            'cashSales', 'upiSales', 'cardSales', 'lowStockItems', 'outOfStockItems',
            'recentInvoices', 'recentMovements'
        ));
    }
}
