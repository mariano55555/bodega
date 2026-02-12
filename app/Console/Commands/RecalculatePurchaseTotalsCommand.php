<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Console\Command;

class RecalculatePurchaseTotalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchases:recalculate-totals
                            {--dry-run : Show what would change without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all purchase detail and purchase totals using the current formula (total = subtotal - discount, IVA is informational)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se harán cambios en la base de datos.');
            $this->newLine();
        }

        // Step 1: Recalculate PurchaseDetail totals
        $this->info('Paso 1: Recalculando totales de líneas de compra (purchase_details)...');

        $details = PurchaseDetail::all();
        $detailsUpdated = 0;
        $detailsSkipped = 0;

        $progressBar = $this->output->createProgressBar($details->count());
        $progressBar->start();

        foreach ($details as $detail) {
            $oldTotal = (float) $detail->total;
            $newSubtotal = $detail->quantity * $detail->unit_cost;
            $discountAmount = $detail->discount_percentage > 0
                ? $newSubtotal * ($detail->discount_percentage / 100)
                : (float) $detail->discount_amount;
            $newTotal = $newSubtotal - $discountAmount;

            if (round($oldTotal, 5) !== round($newTotal, 5)) {
                if (! $dryRun) {
                    $detail->save(); // Triggers the saving event which recalculates
                }
                $detailsUpdated++;
            } else {
                $detailsSkipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Líneas actualizadas: {$detailsUpdated}");
        $this->info("Líneas sin cambios: {$detailsSkipped}");
        $this->newLine();

        // Step 2: Recalculate Purchase totals
        $this->info('Paso 2: Recalculando totales de compras (purchases)...');

        $purchases = Purchase::with('details')->get();
        $purchasesUpdated = 0;
        $purchasesSkipped = 0;

        $progressBar = $this->output->createProgressBar($purchases->count());
        $progressBar->start();

        foreach ($purchases as $purchase) {
            $oldTotal = (float) $purchase->total;

            // Refresh details to get the recalculated values
            $purchase->load('details');

            $newSubtotal = $purchase->details->sum(fn ($d) => $d->quantity * $d->unit_cost);
            $newTotal = $newSubtotal - $purchase->details->sum('discount_amount') + (float) $purchase->shipping_cost;

            if (round($oldTotal, 5) !== round($newTotal, 5)) {
                if (! $dryRun) {
                    $purchase->calculateTotals();
                }
                $purchasesUpdated++;

                if ($dryRun) {
                    $this->newLine();
                    $this->line("  {$purchase->purchase_number}: \${$oldTotal} -> \${$newTotal}");
                }
            } else {
                $purchasesSkipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Compras actualizadas: {$purchasesUpdated}");
        $this->info("Compras sin cambios: {$purchasesSkipped}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('Ejecuta sin --dry-run para aplicar los cambios.');
        } else {
            $this->info('Recálculo completado exitosamente.');
        }

        return Command::SUCCESS;
    }
}
