<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class RecipeConsumptionService {
    public function computeRequiredMaterials(array $items): array {
        $requirements = []; // inventory_item_id => float quantity

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $variantId = $item['variant_id'] ?? null;
            $qty = max(1, (int)$item['quantity']);
            $addonIds = $item['addon_ids'] ?? [];

            // 1. Fetch matching recipes
            $recipes = Recipe::where('product_id', $productId)
                ->where(function ($q) use ($variantId) {
                    $q->where('product_variant_id', $variantId)
                      ->orWhereNull('product_variant_id');
                })
                ->whereNull('addon_id')
                ->get();

            foreach ($recipes as $recipe) {
                $matId = $recipe->inventory_item_id;
                $needed = (float)$recipe->quantity_required * $qty;
                $requirements[$matId] = ($requirements[$matId] ?? 0.0) + $needed;
            }

            // 2. Add-on ingredient BOM
            if (!empty($addonIds)) {
                $addonRecipes = Recipe::whereIn('addon_id', $addonIds)->get();
                foreach ($addonRecipes as $recipe) {
                    $matId = $recipe->inventory_item_id;
                    $needed = (float)$recipe->quantity_required * $qty;
                    $requirements[$matId] = ($requirements[$matId] ?? 0.0) + $needed;
                }
            }
        }

        return $requirements;
    }

    public function deductStockForSale(array $materialRequirements, int $invoiceId, int $userId): void {
        foreach ($materialRequirements as $itemId => $qtyToDeduct) {
            /** @var InventoryItem $material */
            $material = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

            $newStock = (float)$material->current_stock - (float)$qtyToDeduct;
            $material->current_stock = $newStock;
            $material->save();

            StockMovement::create([
                'inventory_item_id' => $material->id,
                'type' => 'sale_deduction',
                'quantity_change' => -$qtyToDeduct,
                'stock_after' => $newStock,
                'reference_type' => 'Invoice',
                'reference_id' => $invoiceId,
                'reason' => "Sale deduction for invoice #{$invoiceId}",
                'user_id' => $userId,
            ]);
        }
    }

    public function reverseStockForSale(int $invoiceId, int $userId, string $reason): void {
        $deductions = StockMovement::where('reference_type', 'Invoice')
            ->where('reference_id', $invoiceId)
            ->where('type', 'sale_deduction')
            ->get();

        foreach ($deductions as $deduction) {
            $material = InventoryItem::where('id', $deduction->inventory_item_id)->lockForUpdate()->firstOrFail();
            $restoreQty = abs((float)$deduction->quantity_change);

            $newStock = (float)$material->current_stock + $restoreQty;
            $material->current_stock = $newStock;
            $material->save();

            StockMovement::create([
                'inventory_item_id' => $material->id,
                'type' => 'reversal',
                'quantity_change' => $restoreQty,
                'stock_after' => $newStock,
                'reference_type' => 'Invoice',
                'reference_id' => $invoiceId,
                'reason' => "Reversal: {$reason}",
                'user_id' => $userId,
            ]);
        }
    }
}
