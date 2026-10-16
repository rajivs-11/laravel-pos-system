<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemAddon;
use App\Models\InvoicePayment;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Addon;
use App\Models\Customer;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Exception;

class BillingService {
    public function __construct(
        protected InvoiceNumberService $invoiceNumberService,
        protected RecipeConsumptionService $recipeConsumptionService,
        protected LoyaltyService $loyaltyService
    ) {}

    public function calculateBill(array $cartItems, float $discountValue = 0, string $discountType = 'fixed', int $redeemPoints = 0): array {
        $subtotal = 0.0;
        $itemsBreakdown = [];

        foreach ($cartItems as $cItem) {
            $product = Product::with('variants')->findOrFail($cItem['product_id']);
            $unitPrice = (float)$product->base_price;
            $variantName = null;

            if (!empty($cItem['variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)->findOrFail($cItem['variant_id']);
                $unitPrice = (float)$variant->price;
                $variantName = $variant->name;
            }

            $addonsList = [];
            $addonsTotal = 0.0;
            if (!empty($cItem['addon_ids'])) {
                $addons = Addon::whereIn('id', $cItem['addon_ids'])->get();
                foreach ($addons as $addon) {
                    $addonsList[] = [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => (float)$addon->price,
                    ];
                    $addonsTotal += (float)$addon->price;
                }
            }

            $lineUnitPrice = $unitPrice + $addonsTotal;
            $qty = max(1, (int)$cItem['quantity']);
            $lineSubtotal = round($lineUnitPrice * $qty, 2);
            $subtotal += $lineSubtotal;

            $itemsBreakdown[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variant_id' => $cItem['variant_id'] ?? null,
                'variant_name' => $variantName,
                'unit_price' => $lineUnitPrice,
                'quantity' => $qty,
                'line_subtotal' => $lineSubtotal,
                'tax_percent' => (float)$product->tax_percent,
                'addons' => $addonsList,
            ];
        }

        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountAmount = round(($subtotal * min(100, max(0, $discountValue))) / 100, 2);
        } else {
            $discountAmount = min($subtotal, max(0, $discountValue));
        }

        $loyaltyDiscount = min($subtotal - $discountAmount, min(100, max(0, $redeemPoints)));
        $taxableAmount = max(0.0, $subtotal - $discountAmount - $loyaltyDiscount);

        // 5% GST (2.5% CGST + 2.5% SGST)
        $cgst = round($taxableAmount * 0.025, 2);
        $sgst = round($taxableAmount * 0.025, 2);
        $igst = 0.00;

        $grossPayable = $taxableAmount + $cgst + $sgst;
        $roundedTotal = round($grossPayable);
        $roundOff = round($roundedTotal - $grossPayable, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'loyalty_discount' => $loyaltyDiscount,
            'taxable_amount' => $taxableAmount,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => $igst,
            'round_off' => $roundOff,
            'total_amount' => $roundedTotal,
            'items' => $itemsBreakdown,
        ];
    }

    public function finalizeInvoice(array $payload, int $cashierId): Invoice {
        return DB::transaction(function () use ($payload, $cashierId) {
            $cartItems = $payload['items'];
            $customerId = $payload['customer_id'] ?? null;
            $orderType = $payload['order_type'] ?? 'takeaway';
            $tableId = $payload['table_id'] ?? null;
            $discountVal = (float)($payload['discount_value'] ?? 0);
            $discountType = $payload['discount_type'] ?? 'fixed';
            $redeemPoints = (int)($payload['redeem_points'] ?? 0);
            $payments = $payload['payments'] ?? [];

            $totals = $this->calculateBill($cartItems, $discountVal, $discountType, $redeemPoints);

            $totalPaid = 0.0;
            foreach ($payments as $p) {
                $totalPaid += (float)$p['amount'];
            }

            if (round($totalPaid, 2) < round($totals['total_amount'], 2)) {
                throw new Exception("Total payments (₹{$totalPaid}) must cover total payable (₹{$totals['total_amount']}).");
            }

            $invoiceNumber = $this->invoiceNumberService->generateNextInvoiceNumber();

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customerId,
                'user_id' => $cashierId,
                'order_type' => $orderType,
                'table_id' => $tableId,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'discount_reason' => $payload['discount_reason'] ?? null,
                'loyalty_points_redeemed' => $redeemPoints,
                'loyalty_discount_amount' => $totals['loyalty_discount'],
                'taxable_amount' => $totals['taxable_amount'],
                'cgst_amount' => $totals['cgst_amount'],
                'sgst_amount' => $totals['sgst_amount'],
                'igst_amount' => $totals['igst_amount'],
                'round_off' => $totals['round_off'],
                'total_amount' => $totals['total_amount'],
                'status' => 'paid',
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($totals['items'] as $itemData) {
                $lineTax = round(($itemData['line_subtotal'] * ($itemData['tax_percent'] / 100)), 2);
                $invItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $itemData['product_id'],
                    'product_variant_id' => $itemData['variant_id'],
                    'product_name' => $itemData['product_name'],
                    'variant_name' => $itemData['variant_name'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'line_subtotal' => $itemData['line_subtotal'],
                    'tax_percent' => $itemData['tax_percent'],
                    'tax_amount' => $lineTax,
                    'line_total' => $itemData['line_subtotal'] + $lineTax,
                ]);

                foreach ($itemData['addons'] as $addon) {
                    InvoiceItemAddon::create([
                        'invoice_item_id' => $invItem->id,
                        'addon_id' => $addon['id'],
                        'addon_name' => $addon['name'],
                        'price' => $addon['price'],
                    ]);
                }
            }

            foreach ($payments as $payment) {
                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_method' => $payment['method'],
                    'amount' => (float)$payment['amount'],
                    'tendered_amount' => isset($payment['tendered']) ? (float)$payment['tendered'] : null,
                    'change_amount' => isset($payment['change']) ? (float)$payment['change'] : null,
                    'transaction_reference' => $payment['reference'] ?? null,
                    'user_id' => $cashierId,
                ]);
            }

            // Deduct recipe BOM stock
            $materialsNeeded = $this->recipeConsumptionService->computeRequiredMaterials($cartItems);
            $this->recipeConsumptionService->deductStockForSale($materialsNeeded, $invoice->id, $cashierId);

            // Loyalty points
            if ($customerId) {
                if ($redeemPoints > 0) {
                    $this->loyaltyService->redeemPoints($customerId, $redeemPoints, $invoice->id);
                }
                $pointsEarned = (int)floor($totals['taxable_amount'] / 10);
                if ($pointsEarned > 0) {
                    $this->loyaltyService->awardPoints($customerId, $pointsEarned, $invoice->id);
                }

                Customer::where('id', $customerId)->increment('total_visits', 1);
                Customer::where('id', $customerId)->increment('total_spend', $totals['total_amount']);
                Customer::where('id', $customerId)->update(['last_visit_at' => now()]);
            }

            // Kitchen Order Ticket
            $kot = KitchenOrder::create([
                'kot_number' => 'KOT-' . str_pad((string)$invoice->id, 5, '0', STR_PAD_LEFT),
                'invoice_id' => $invoice->id,
                'table_name' => $tableId ? RestaurantTable::find($tableId)?->name : null,
                'order_type' => $orderType,
                'status' => 'pending',
                'special_instructions' => $payload['kitchen_notes'] ?? null,
            ]);

            foreach ($totals['items'] as $itemData) {
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kot->id,
                    'item_name' => $itemData['product_name'],
                    'variant_name' => $itemData['variant_name'],
                    'quantity' => $itemData['quantity'],
                    'addons' => json_encode(array_column($itemData['addons'], 'name')),
                ]);
            }

            return $invoice->load(['items.addons', 'payments', 'customer']);
        });
    }
}
