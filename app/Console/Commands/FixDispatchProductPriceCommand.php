<?php

namespace App\Console\Commands;

use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDispatchProductPriceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:fix-product-price
                            {--product= : Nombre o ID del producto a corregir}
                            {--old-price= : Precio incorrecto actual}
                            {--new-price= : Precio correcto a establecer}
                            {--dry-run : Mostrar cambios sin aplicarlos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige el precio de un producto en todos los despachos donde aparece con un precio incorrecto';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $productSearch = $this->option('product');
        $oldPrice = $this->option('old-price');
        $newPrice = $this->option('new-price');

        // Interactive mode if options not provided
        if (! $productSearch) {
            $productSearch = $this->ask('Ingresa el nombre o ID del producto');
        }

        if (! $oldPrice) {
            $oldPrice = $this->ask('Ingresa el precio incorrecto actual');
        }

        if (! $newPrice) {
            $newPrice = $this->ask('Ingresa el precio correcto');
        }

        // Validate prices
        $oldPrice = (float) $oldPrice;
        $newPrice = (float) $newPrice;

        if ($oldPrice <= 0 || $newPrice <= 0) {
            $this->error('Los precios deben ser mayores a 0.');

            return Command::FAILURE;
        }

        if ($oldPrice === $newPrice) {
            $this->error('El precio viejo y nuevo no pueden ser iguales.');

            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se harán cambios en la base de datos.');
            $this->newLine();
        }

        // Find product
        $product = $this->findProduct($productSearch);

        if (! $product) {
            $this->error("No se encontró el producto: {$productSearch}");

            return Command::FAILURE;
        }

        $this->info("Producto encontrado: {$product->name} (ID: {$product->id}, SKU: {$product->sku})");
        $this->newLine();

        // Find affected dispatch details
        $affectedDetails = DispatchDetail::where('product_id', $product->id)
            ->whereBetween('unit_price', [$oldPrice - 0.001, $oldPrice + 0.001])
            ->with(['dispatch:id,dispatch_number,status,total'])
            ->get();

        $count = $affectedDetails->count();

        if ($count === 0) {
            $this->info("No se encontraron despachos con el producto a precio \${$oldPrice}.");

            return Command::SUCCESS;
        }

        $this->warn("Se encontraron {$count} líneas de despacho con precio incorrecto (\${$oldPrice}).");
        $this->newLine();

        // Show affected dispatches
        $this->info('Despachos afectados:');
        $tableData = $affectedDetails->map(fn ($detail) => [
            $detail->id,
            $detail->dispatch->dispatch_number ?? 'N/A',
            $detail->dispatch->status ?? 'N/A',
            number_format($detail->quantity, 2),
            '$'.number_format($detail->unit_price, 2),
            '$'.number_format($newPrice, 2),
            '$'.number_format($detail->subtotal, 2),
            '$'.number_format($detail->quantity * $newPrice, 2),
        ])->toArray();

        $this->table(
            ['Detail ID', 'Despacho', 'Estado', 'Cantidad', 'Precio Actual', 'Precio Nuevo', 'Subtotal Actual', 'Subtotal Nuevo'],
            array_slice($tableData, 0, 20)
        );

        if ($count > 20) {
            $this->line('... y '.($count - 20).' más.');
        }

        // Calculate totals
        $totalDifference = $affectedDetails->sum(function ($detail) use ($oldPrice, $newPrice) {
            return $detail->quantity * ($oldPrice - $newPrice);
        });

        $this->newLine();
        $this->info('Diferencia total: $'.number_format($totalDifference, 2));
        $this->newLine();

        // Find affected inventory movements
        $affectedMovements = InventoryMovement::where('product_id', $product->id)
            ->whereNotNull('dispatch_id')
            ->whereBetween('unit_cost', [$oldPrice - 0.001, $oldPrice + 0.001])
            ->get();

        $movementsCount = $affectedMovements->count();

        if ($movementsCount > 0) {
            $this->info("También se actualizarán {$movementsCount} movimientos de inventario relacionados.");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn("Se actualizarían {$count} líneas de despacho de \${$oldPrice} a \${$newPrice}.");
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');

            return Command::SUCCESS;
        }

        // Confirm
        if (! $this->confirm("¿Deseas actualizar {$count} líneas de despacho de \${$oldPrice} a \${$newPrice}?")) {
            $this->info('Operación cancelada.');

            return Command::SUCCESS;
        }

        // Perform update
        DB::beginTransaction();

        try {
            $updatedDetails = 0;
            $updatedDispatches = [];

            foreach ($affectedDetails as $detail) {
                // Update unit_price
                $detail->unit_price = $newPrice;

                // Recalculate totals (the model's saving event will handle this)
                $detail->save();

                $updatedDetails++;

                // Track dispatches to update
                if ($detail->dispatch_id && ! in_array($detail->dispatch_id, $updatedDispatches)) {
                    $updatedDispatches[] = $detail->dispatch_id;
                }
            }

            // Update dispatch totals
            foreach ($updatedDispatches as $dispatchId) {
                $dispatch = Dispatch::find($dispatchId);
                if ($dispatch) {
                    $dispatch->calculateTotals();
                }
            }

            // Update inventory movements
            $updatedMovements = 0;
            foreach ($affectedMovements as $movement) {
                $movement->unit_cost = $newPrice;
                $movement->total_cost = $movement->quantity_out * $newPrice;
                $movement->save();
                $updatedMovements++;
            }

            DB::commit();

            $this->newLine();
            $this->info("✓ Se actualizaron {$updatedDetails} líneas de despacho.");
            $this->info('✓ Se recalcularon los totales de '.count($updatedDispatches).' despachos.');

            if ($updatedMovements > 0) {
                $this->info("✓ Se actualizaron {$updatedMovements} movimientos de inventario.");
            }

            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error al actualizar: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Find product by ID or name.
     */
    private function findProduct(string $search): ?Product
    {
        // Try by ID first
        if (is_numeric($search)) {
            $product = Product::find((int) $search);
            if ($product) {
                return $product;
            }
        }

        // Try exact name match
        $product = Product::where('name', $search)->first();
        if ($product) {
            return $product;
        }

        // Try partial name match
        $products = Product::where('name', 'like', "%{$search}%")->get();

        if ($products->count() === 0) {
            return null;
        }

        if ($products->count() === 1) {
            return $products->first();
        }

        // Multiple matches - let user choose
        $this->warn("Se encontraron {$products->count()} productos con ese nombre:");

        $choices = $products->mapWithKeys(fn ($p) => [$p->id => "{$p->name} (SKU: {$p->sku})"]);

        $selectedId = $this->choice(
            'Selecciona el producto correcto:',
            $choices->toArray()
        );

        // Find the ID from the selected value
        $selectedProduct = $products->first(fn ($p) => "{$p->name} (SKU: {$p->sku})" === $selectedId);

        return $selectedProduct;
    }
}
