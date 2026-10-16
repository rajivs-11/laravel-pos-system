<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RestaurantTable;
use App\Models\Customer;
use App\Models\HeldOrder;
use App\Models\Invoice;
use App\Models\KitchenOrder;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Exception;

class PosController extends Controller {
    public function __construct(protected BillingService $billingService) {}

    public function index() {
        $categories = ProductCategory::where('is_active', true)->orderBy('sort_order')->get();
        $products = Product::with(['variants', 'addons'])->where('is_active', true)->get();
        $tables = RestaurantTable::all();
        $heldCount = HeldOrder::count();

        return view('pos.index', compact('categories', 'products', 'tables', 'heldCount'));
    }

    public function lookupCustomer(Request $request) {
        $query = $request->get('q');
        $customer = Customer::where('phone', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->first();

        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function quickCreateCustomer(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:15|unique:customers,phone',
            'email' => 'nullable|email',
        ]);

        $customer = Customer::create($validated);
        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function calculateTotals(Request $request) {
        $cartItems = $request->input('items', []);
        $discountType = $request->input('discount_type', 'fixed');
        $discountValue = (float)$request->input('discount_value', 0);
        $redeemPoints = (int)$request->input('redeem_points', 0);

        $totals = $this->billingService->calculateBill($cartItems, $discountValue, $discountType, $redeemPoints);
        return response()->json($totals);
    }

    public function finalizeOrder(Request $request) {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'order_type' => 'required|in:takeaway,dine_in,delivery',
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_type' => 'nullable|in:fixed,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string',
            'redeem_points' => 'nullable|integer|min:0',
            'payments' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $invoice = $this->billingService->finalizeInvoice($validated, auth()->id() ?? 1);
            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function holdOrder(Request $request) {
        $validated = $request->validate([
            'order_type' => 'required',
            'table_id' => 'nullable',
            'cart_data' => 'required|array',
            'cart_total' => 'required|numeric',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ]);

        $holdRef = 'HOLD-' . strtoupper(substr(uniqid(), -6));
        $held = HeldOrder::create([
            'hold_ref' => $holdRef,
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'order_type' => $validated['order_type'],
            'table_id' => $validated['table_id'] ?? null,
            'user_id' => auth()->id() ?? 1,
            'cart_data' => json_encode($validated['cart_data']),
            'cart_total' => $validated['cart_total'],
        ]);

        return response()->json(['success' => true, 'hold_ref' => $holdRef, 'held_id' => $held->id]);
    }

    public function getHeldOrders() {
        $held = HeldOrder::with('table')->latest()->get();
        return response()->json(['success' => true, 'orders' => $held]);
    }

    public function resumeOrder($id) {
        $held = HeldOrder::findOrFail($id);
        $cartData = json_decode($held->cart_data, true);
        $orderMeta = [
            'order_type' => $held->order_type,
            'table_id' => $held->table_id,
            'customer_name' => $held->customer_name,
            'customer_phone' => $held->customer_phone,
        ];
        $held->delete();

        return response()->json(['success' => true, 'cart' => $cartData, 'meta' => $orderMeta]);
    }

    public function cancelHeldOrder($id) {
        HeldOrder::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function printReceipt($id) {
        $invoice = Invoice::with(['items.addons', 'payments', 'customer', 'user', 'table'])->findOrFail($id);
        $settings = DB::table('settings')->pluck('value', 'key')->toArray();
        return view('pos.receipt', compact('invoice', 'settings'));
    }

    public function printKot($id) {
        $kot = KitchenOrder::with(['items', 'invoice'])->findOrFail($id);
        return view('pos.kot', compact('kot'));
    }
}
