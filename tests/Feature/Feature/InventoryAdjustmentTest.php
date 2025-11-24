<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('Inventory Adjustment Workflow', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create([
            'cost' => 10.00,
        ]);

        // Create initial inventory movement
        InventoryMovement::factory()->create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'movement_type' => 'in',
            'quantity_in' => 100,
            'balance_quantity' => 100,
            'unit_cost' => 10.00,
        ]);
    });

    describe('Adjustment Creation', function () {
        it('can create a draft adjustment', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'adjustment_type' => 'correction',
                'quantity' => 5,
                'unit_cost' => 10.00,
                'reason' => 'Corrección de conteo físico',
                'status' => 'borrador',
            ]);

            expect($adjustment->status)->toBe('borrador');
            expect($adjustment->adjustment_number)->not->toBeEmpty();
            expect($adjustment->slug)->not->toBeEmpty();
            expect($adjustment->created_by)->toBe($this->user->id);
        });

        it('generates adjustment number and slug automatically', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->adjustment_number)->toMatch('/^ADJ-\d{8}-[A-Z0-9]{6}$/');
            expect($adjustment->slug)->not->toBeEmpty();
        });

        it('calculates total value automatically', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 5.50,
            ]);

            expect($adjustment->total_value)->toBe(55.00);
        });

        it('handles negative quantities for negative adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'adjustment_type' => 'damage',
                'quantity' => -10,
                'unit_cost' => 10.00,
            ]);

            expect($adjustment->quantity)->toBe(-10.0);
            expect($adjustment->total_value)->toBe(100.00); // abs value
            expect($adjustment->isNegativeAdjustment())->toBeTrue();
        });
    });

    describe('Adjustment Workflow - Submit', function () {
        it('can submit a draft adjustment for approval', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->draft()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeSubmitted())->toBeTrue();

            $result = $adjustment->submit($this->user->id);

            expect($result)->toBeTrue();
            expect($adjustment->fresh()->status)->toBe('pendiente');
            expect($adjustment->fresh()->submitted_by)->toBe($this->user->id);
            expect($adjustment->fresh()->submitted_at)->not->toBeNull();
        });

        it('cannot submit non-draft adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->pending()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeSubmitted())->toBeFalse();

            $result = $adjustment->submit($this->user->id);

            expect($result)->toBeFalse();
        });
    });

    describe('Adjustment Workflow - Approve', function () {
        it('can approve a pending adjustment', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->pending()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeApproved())->toBeTrue();

            $result = $adjustment->approve($this->user->id, 'Aprobado por revisión');

            expect($result)->toBeTrue();
            expect($adjustment->fresh()->status)->toBe('aprobado');
            expect($adjustment->fresh()->approved_by)->toBe($this->user->id);
            expect($adjustment->fresh()->approved_at)->not->toBeNull();
            expect($adjustment->fresh()->approval_notes)->toBe('Aprobado por revisión');
        });

        it('cannot approve non-pending adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->draft()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeApproved())->toBeFalse();

            $result = $adjustment->approve($this->user->id);

            expect($result)->toBeFalse();
        });
    });

    describe('Adjustment Workflow - Reject', function () {
        it('can reject a pending adjustment with reason', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->pending()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeRejected())->toBeTrue();

            $result = $adjustment->reject($this->user->id, 'Falta documentación de respaldo');

            expect($result)->toBeTrue();
            expect($adjustment->fresh()->status)->toBe('rechazado');
            expect($adjustment->fresh()->rejected_by)->toBe($this->user->id);
            expect($adjustment->fresh()->rejected_at)->not->toBeNull();
            expect($adjustment->fresh()->rejection_reason)->toBe('Falta documentación de respaldo');
        });

        it('cannot reject non-pending adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->approved()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeRejected())->toBeFalse();

            $result = $adjustment->reject($this->user->id, 'Test rejection');

            expect($result)->toBeFalse();
        });
    });

    describe('Adjustment Workflow - Process', function () {
        it('can process an approved adjustment and create inventory movement', function () {
            actingAs($this->user);

            // Create movement reason for adjustments
            \App\Models\MovementReason::factory()->create([
                'company_id' => $this->company->id,
                'code' => 'ADJUSTMENT_CORRECTION',
                'name' => 'Ajuste por Corrección',
                'movement_type' => 'in',
            ]);

            $adjustment = InventoryAdjustment::factory()->approved()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'adjustment_type' => 'correction',
                'quantity' => 5,
                'unit_cost' => 10.00,
            ]);

            expect($adjustment->canBeProcessed())->toBeTrue();

            $result = $adjustment->process($this->user->id);

            expect($result)->toBeTrue();
            expect($adjustment->fresh()->status)->toBe('procesado');
            expect($adjustment->fresh()->processed_by)->toBe($this->user->id);
            expect($adjustment->fresh()->processed_at)->not->toBeNull();
            expect($adjustment->fresh()->inventory_movement_id)->not->toBeNull();

            // Verify inventory movement was created
            $movement = InventoryMovement::find($adjustment->fresh()->inventory_movement_id);
            expect($movement)->not->toBeNull();
            expect($movement->document_number)->toBe($adjustment->adjustment_number);
            expect($movement->balance_quantity)->toBe(105.0); // 100 initial + 5 adjustment
        });

        it('processes negative adjustments correctly', function () {
            actingAs($this->user);

            \App\Models\MovementReason::factory()->create([
                'company_id' => $this->company->id,
                'code' => 'ADJUSTMENT_DAMAGE',
                'name' => 'Ajuste por Daño',
                'movement_type' => 'out',
            ]);

            $adjustment = InventoryAdjustment::factory()->approved()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'adjustment_type' => 'damage',
                'quantity' => -10,
                'unit_cost' => 10.00,
            ]);

            $result = $adjustment->process($this->user->id);

            expect($result)->toBeTrue();

            $movement = InventoryMovement::find($adjustment->fresh()->inventory_movement_id);
            expect($movement->movement_type)->toBe('out');
            expect($movement->quantity_out)->toBe(10.0);
            expect($movement->balance_quantity)->toBe(90.0); // 100 - 10
        });

        it('cannot process non-approved adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->pending()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeProcessed())->toBeFalse();

            $result = $adjustment->process($this->user->id);

            expect($result)->toBeFalse();
        });
    });

    describe('Adjustment Cancellation', function () {
        it('can cancel adjustments that are not processed', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->pending()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeCancelled())->toBeTrue();

            $result = $adjustment->cancel();

            expect($result)->toBeTrue();
            expect($adjustment->fresh()->status)->toBe('cancelado');
        });

        it('cannot cancel processed adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->processed()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeCancelled())->toBeFalse();

            $result = $adjustment->cancel();

            expect($result)->toBeFalse();
        });
    });

    describe('Adjustment Editing', function () {
        it('can edit draft adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->draft()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeEdited())->toBeTrue();
        });

        it('can edit rejected adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->rejected()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($adjustment->canBeEdited())->toBeTrue();
        });

        it('cannot edit approved or processed adjustments', function () {
            actingAs($this->user);

            $approvedAdjustment = InventoryAdjustment::factory()->approved()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            $processedAdjustment = InventoryAdjustment::factory()->processed()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            expect($approvedAdjustment->canBeEdited())->toBeFalse();
            expect($processedAdjustment->canBeEdited())->toBeFalse();
        });
    });

    describe('Adjustment Types', function () {
        it('correctly identifies positive adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'adjustment_type' => 'positive',
                'quantity' => 10,
            ]);

            expect($adjustment->isPositiveAdjustment())->toBeTrue();
            expect($adjustment->isNegativeAdjustment())->toBeFalse();
        });

        it('correctly identifies negative adjustments', function () {
            actingAs($this->user);

            $adjustment = InventoryAdjustment::factory()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'adjustment_type' => 'damage',
                'quantity' => -10,
            ]);

            expect($adjustment->isNegativeAdjustment())->toBeTrue();
            expect($adjustment->isPositiveAdjustment())->toBeFalse();
        });

        it('translates adjustment types to spanish', function () {
            $types = [
                'positive' => 'Ajuste Positivo',
                'negative' => 'Ajuste Negativo',
                'damage' => 'Producto Dañado',
                'expiry' => 'Producto Vencido',
                'loss' => 'Pérdida/Robo',
                'correction' => 'Corrección de Conteo',
                'return' => 'Devolución',
                'other' => 'Otro',
            ];

            foreach ($types as $type => $spanish) {
                $adjustment = InventoryAdjustment::factory()->create([
                    'company_id' => $this->company->id,
                    'warehouse_id' => $this->warehouse->id,
                    'product_id' => $this->product->id,
                    'adjustment_type' => $type,
                ]);

                expect($adjustment->adjustment_type_spanish)->toBe($spanish);
            }
        });
    });

    describe('Adjustment Scopes', function () {
        it('filters by company', function () {
            actingAs($this->user);

            $otherCompany = Company::factory()->create();

            InventoryAdjustment::factory()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            $otherWarehouse = Warehouse::factory()->forCompany($otherCompany)->create();
            $otherProduct = Product::factory()->forCompany($otherCompany)->create();

            InventoryAdjustment::factory()->create([
                'company_id' => $otherCompany->id,
                'warehouse_id' => $otherWarehouse->id,
                'product_id' => $otherProduct->id,
            ]);

            $adjustments = InventoryAdjustment::forCompany($this->company->id)->get();

            expect($adjustments)->toHaveCount(1);
            expect($adjustments->first()->company_id)->toBe($this->company->id);
        });

        it('filters by status', function () {
            actingAs($this->user);

            InventoryAdjustment::factory()->draft()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            InventoryAdjustment::factory()->pending()->create([
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
            ]);

            $drafts = InventoryAdjustment::draft()->get();
            $pending = InventoryAdjustment::pending()->get();

            expect($drafts)->toHaveCount(1);
            expect($pending)->toHaveCount(1);
        });
    });
});
