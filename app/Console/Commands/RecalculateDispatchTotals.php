<?php

namespace App\Console\Commands;

use App\Models\Dispatch;
use App\Models\DispatchDetail;
use Illuminate\Console\Command;

class RecalculateDispatchTotals extends Command
{
    protected $signature = 'app:recalculate-dispatch-totals
                            {--dry-run : Mostrar los cambios sin aplicarlos}';

    protected $description = 'Recalcula subtotales de detalles y totales de despachos';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Modo dry-run: no se aplicarán cambios.');
        }

        // 1. Fix detail subtotals
        $this->info('Verificando subtotales de detalles...');
        $detailsFixed = 0;

        DispatchDetail::whereHas('dispatch', fn ($q) => $q->whereNull('deleted_at'))
            ->chunk(200, function ($details) use ($dryRun, &$detailsFixed) {
                foreach ($details as $detail) {
                    $correctSubtotal = (float) $detail->quantity * (float) $detail->unit_price;

                    if (abs((float) $detail->subtotal - $correctSubtotal) > 0.001) {
                        $this->line("  Detalle #{$detail->id} (Despacho #{$detail->dispatch_id}): subtotal {$detail->subtotal} -> {$correctSubtotal}");

                        if (! $dryRun) {
                            $detail->save(); // triggers saving event
                        }

                        $detailsFixed++;
                    }
                }
            });

        $this->info("Detalles corregidos: {$detailsFixed}");

        // 2. Recalculate dispatch totals
        $this->info('Verificando totales de despachos...');
        $dispatchesFixed = 0;

        Dispatch::withoutTrashed()
            ->with('details')
            ->chunk(100, function ($dispatches) use ($dryRun, &$dispatchesFixed) {
                foreach ($dispatches as $dispatch) {
                    $correctSubtotal = $dispatch->details->sum(fn ($d) => (float) $d->subtotal);
                    $correctTax = $dispatch->details->sum(fn ($d) => (float) $d->tax_amount);
                    $correctDiscount = $dispatch->details->sum(fn ($d) => (float) $d->discount_amount);
                    $correctTotal = $correctSubtotal + $correctTax - $correctDiscount + (float) $dispatch->shipping_cost;

                    $subtotalDiff = abs((float) $dispatch->subtotal - $correctSubtotal);
                    $totalDiff = abs((float) $dispatch->total - $correctTotal);

                    if ($subtotalDiff > 0.001 || $totalDiff > 0.001) {
                        $this->line("  {$dispatch->dispatch_number}: subtotal {$dispatch->subtotal} -> {$correctSubtotal}, total {$dispatch->total} -> {$correctTotal}");

                        if (! $dryRun) {
                            $dispatch->subtotal = $correctSubtotal;
                            $dispatch->tax_amount = $correctTax;
                            $dispatch->discount_amount = $correctDiscount;
                            $dispatch->total = $correctTotal;
                            $dispatch->save();
                        }

                        $dispatchesFixed++;
                    }
                }
            });

        $this->info("Despachos corregidos: {$dispatchesFixed}");

        if ($dryRun && ($detailsFixed > 0 || $dispatchesFixed > 0)) {
            $this->warn('Ejecuta sin --dry-run para aplicar los cambios.');
        }

        return self::SUCCESS;
    }
}
