<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseDetail;
use Illuminate\Database\Seeder;

class UpdateProductCostsFromPurchasesSeeder extends Seeder
{
    /**
     * Update product costs from the latest received purchase.
     */
    public function run(): void
    {
        $this->command->info('Actualizando costos de productos desde última compra recibida...');

        $products = Product::all();
        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $latestDetail = PurchaseDetail::where('product_id', $product->id)
                ->whereHas('purchase', fn ($q) => $q->where('status', 'recibido'))
                ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                ->orderByDesc('purchases.document_date')
                ->orderByDesc('purchases.received_at')
                ->orderByDesc('purchase_details.id')
                ->select('purchase_details.*')
                ->first();

            if (! $latestDetail) {
                $skipped++;

                continue;
            }

            $oldCost = (float) ($product->cost ?? 0);
            $newCost = (float) $latestDetail->unit_cost;

            if (round($oldCost, 5) === round($newCost, 5)) {
                $skipped++;

                continue;
            }

            $costChange = $newCost - $oldCost;
            $changePercentage = $oldCost > 0
                ? round(($costChange / $oldCost) * 100, 2)
                : 0;

            ProductPriceHistory::create([
                'product_id' => $product->id,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'cost_change' => $costChange,
                'change_percentage' => $changePercentage,
                'source_type' => 'purchase',
                'source_id' => $latestDetail->purchase_id,
                'notes' => 'Corrección de datos: actualización desde compra recibida',
                'is_active' => true,
                'active_at' => now(),
            ]);

            $product->cost = $newCost;
            $product->save();
            $updated++;

            $this->command->line("  {$product->name} ({$product->sku}): {$oldCost} -> {$newCost}");
        }

        $this->command->newLine();
        $this->command->info("Productos actualizados: {$updated}");
        $this->command->info("Sin cambios: {$skipped}");
    }
}
