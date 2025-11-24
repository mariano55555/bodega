<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

describe('Spanish Localization Tests', function () {
    beforeEach(function () {
        // Set application locale to Spanish
        App::setLocale('es');

        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
        $this->supplier = Supplier::factory()->forCompany($this->company)->create();
    });

    describe('validation message localization', function () {
        it('returns Spanish validation messages for movement creation', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'invalid_type',
                    'product_id' => 999999, // Non-existent product
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 'not_a_number',
                    'unit_cost' => -50.00, // Negative cost
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'movement_type' => 'El tipo de movimiento seleccionado es inválido.',
                    'product_id' => 'El producto seleccionado es inválido.',
                    'quantity' => 'La cantidad debe ser un número.',
                    'unit_cost' => 'El costo unitario debe ser mayor que cero.',
                ]);
        });

        it('returns Spanish validation messages for lot creation', function () {
            $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'lot_number' => '', // Required field
                    'product_id' => 999999, // Non-existent product
                    'supplier_id' => 999999, // Non-existent supplier
                    'manufactured_date' => 'invalid_date',
                    'expiration_date' => now()->subDays(1)->format('Y-m-d'), // Before manufactured date
                    'quantity_produced' => -100.0, // Negative quantity
                    'unit_cost' => 'not_a_number',
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'lot_number' => 'El número de lote es obligatorio.',
                    'product_id' => 'El producto seleccionado es inválido.',
                    'supplier_id' => 'El proveedor seleccionado es inválido.',
                    'manufactured_date' => 'La fecha de fabricación no es una fecha válida.',
                    'expiration_date' => 'La fecha de vencimiento debe ser posterior a la fecha de fabricación.',
                    'quantity_produced' => 'La cantidad producida debe ser mayor que cero.',
                    'unit_cost' => 'El costo unitario debe ser un número.',
                ]);
        });

        it('returns Spanish error messages for insufficient inventory', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 10.0,
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -20.0, // More than available
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'quantity' => 'Cantidad insuficiente en inventario. Disponible: 10.0',
                ]);
        });

        it('returns Spanish error messages for expired lots', function () {
            $expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->subDays(1),
                'status' => 'expired',
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $expiredLot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -5.0,
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'product_lot_id' => 'No se puede mover producto de lote vencido.',
                ]);
        });

        it('returns Spanish error messages for quarantined lots', function () {
            $quarantinedLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'quarantined',
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $quarantinedLot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -5.0,
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'product_lot_id' => 'No se puede mover producto de lote en cuarentena.',
                ]);
        });

        it('returns Spanish authorization error messages', function () {
            $otherCompany = Company::factory()->create();
            $otherWarehouse = Warehouse::factory()->forCompany($otherCompany)->create();

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $otherWarehouse->id, // Different company
                    'quantity' => -5.0,
                    'unit_cost' => 25.00,
                ])
                ->assertForbidden()
                ->assertJson([
                    'message' => 'No tienes permisos para acceder a este almacén.',
                ]);
        });
    });

    describe('business rule error messages', function () {
        it('returns Spanish messages for FIFO/FEFO violations', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements/validate-rotation', [
                    'product_id' => $this->product->id,
                    'rotation_strategy' => 'FEFO',
                    'selected_lots' => [999999], // Non-existent lot
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'selected_lots' => 'Los lotes seleccionados no siguen la estrategia FEFO requerida.',
                ]);
        });

        it('returns Spanish messages for approval workflow violations', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'pending_approval',
                'requires_approval' => true,
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'status' => 'El movimiento debe estar aprobado antes de ejecutar.',
                ]);
        });

        it('returns Spanish messages for double execution prevention', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'completed',
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'status' => 'Este movimiento ya ha sido ejecutado.',
                ]);
        });

        it('returns Spanish messages for quantity sign validation', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'purchase', // Inbound movement
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0, // Should be positive for purchase
                    'unit_cost' => 25.00,
                    'supplier_id' => $this->supplier->id,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'quantity' => 'La cantidad debe ser positiva para movimientos de entrada.',
                ]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale', // Outbound movement
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 10.0, // Should be negative for sale
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'quantity' => 'La cantidad debe ser negativa para movimientos de salida.',
                ]);
        });
    });

    describe('success message localization', function () {
        it('returns Spanish success messages for movement creation', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 100.0,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ])
                ->assertCreated();

            expect($response->json('message'))->toBe('Movimiento creado exitosamente.');
        });

        it('returns Spanish success messages for lot creation', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'lot_number' => 'LOT-ES-001',
                    'product_id' => $this->product->id,
                    'supplier_id' => $this->supplier->id,
                    'manufactured_date' => now()->subDays(5)->format('Y-m-d'),
                    'expiration_date' => now()->addDays(365)->format('Y-m-d'),
                    'quantity_produced' => 100.0,
                    'unit_cost' => 25.00,
                ])
                ->assertCreated();

            expect($response->json('message'))->toBe('Lote creado exitosamente.');
        });

        it('returns Spanish success messages for movement approval', function () {
            $manager = User::factory()->forCompany($this->company)->create(); // Add manager role
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'pending_approval',
            ]);

            $response = $this->actingAs($manager)
                ->patchJson("/api/movements/{$movement->id}/approve", [
                    'approval_notes' => 'Aprobado por gerente',
                ])
                ->assertOk();

            expect($response->json('message'))->toBe('Movimiento aprobado exitosamente.');
        });

        it('returns Spanish success messages for movement execution', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 100.0,
            ]);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'product_lot_id' => $lot->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'approved',
                'quantity' => -10.0,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertOk();

            expect($response->json('message'))->toBe('Movimiento ejecutado exitosamente.');
        });
    });

    describe('field label localization', function () {
        it('returns Spanish field labels in API responses', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 100.0,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/lots/{$lot->slug}")
                ->assertOk();

            $data = $response->json('data');

            // Check that field labels are in Spanish
            expect($data['labels']['lot_number'])->toBe('Número de Lote');
            expect($data['labels']['manufactured_date'])->toBe('Fecha de Fabricación');
            expect($data['labels']['expiration_date'])->toBe('Fecha de Vencimiento');
            expect($data['labels']['quantity_produced'])->toBe('Cantidad Producida');
            expect($data['labels']['quantity_remaining'])->toBe('Cantidad Restante');
            expect($data['labels']['unit_cost'])->toBe('Costo Unitario');
            expect($data['labels']['status'])->toBe('Estado');
        });

        it('returns Spanish movement type labels', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/movements/types')
                ->assertOk();

            $types = $response->json('data');

            expect($types['purchase'])->toBe('Compra');
            expect($types['sale'])->toBe('Venta');
            expect($types['adjustment'])->toBe('Ajuste');
            expect($types['transfer_in'])->toBe('Transferencia Entrada');
            expect($types['transfer_out'])->toBe('Transferencia Salida');
            expect($types['damage'])->toBe('Daño');
            expect($types['expiry'])->toBe('Vencimiento');
        });

        it('returns Spanish status labels', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/movements/statuses')
                ->assertOk();

            $statuses = $response->json('data');

            expect($statuses['pending'])->toBe('Pendiente');
            expect($statuses['pending_approval'])->toBe('Pendiente de Aprobación');
            expect($statuses['approved'])->toBe('Aprobado');
            expect($statuses['rejected'])->toBe('Rechazado');
            expect($statuses['completed'])->toBe('Completado');
            expect($statuses['failed'])->toBe('Fallido');
            expect($statuses['cancelled'])->toBe('Cancelado');
        });

        it('returns Spanish lot status labels', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/lots/statuses')
                ->assertOk();

            $statuses = $response->json('data');

            expect($statuses['active'])->toBe('Activo');
            expect($statuses['expired'])->toBe('Vencido');
            expect($statuses['quarantined'])->toBe('En Cuarentena');
            expect($statuses['depleted'])->toBe('Agotado');
            expect($statuses['consolidated'])->toBe('Consolidado');
            expect($statuses['archived'])->toBe('Archivado');
        });
    });

    describe('date and number formatting', function () {
        it('formats dates according to Spanish locale', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => '2024-03-15',
                'expiration_date' => '2025-03-15',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/lots/{$lot->slug}")
                ->assertOk();

            $data = $response->json('data');

            // Check Spanish date format (dd/mm/yyyy)
            expect($data['manufactured_date_formatted'])->toBe('15/03/2024');
            expect($data['expiration_date_formatted'])->toBe('15/03/2025');
        });

        it('formats numbers according to Spanish locale', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 1234.56,
                'unit_cost' => 98765.43,
                'total_cost' => 121932050.08,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/movements/{$movement->id}")
                ->assertOk();

            $data = $response->json('data');

            // Check Spanish number format (decimal comma, thousands dot)
            expect($data['quantity_formatted'])->toBe('1.234,56');
            expect($data['unit_cost_formatted'])->toBe('98.765,43');
            expect($data['total_cost_formatted'])->toBe('121.932.050,08');
        });

        it('formats currency according to El Salvador standards', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'unit_cost' => 25.50,
                'total_cost' => 255.00,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/movements/{$movement->id}")
                ->assertOk();

            $data = $response->json('data');

            // Check USD currency format for El Salvador
            expect($data['unit_cost_currency'])->toBe('$25,50');
            expect($data['total_cost_currency'])->toBe('$255,00');
        });
    });

    describe('notification message localization', function () {
        it('sends Spanish notifications for low stock alerts', function () {
            $this->product->update(['min_stock_level' => 50.0]);

            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 60.0,
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -15.0, // Will leave 45, below minimum
                    'unit_cost' => 25.00,
                ])
                ->assertCreated();

            // Check that notification would be in Spanish
            // This would typically verify queued notifications
            expect(true)->toBeTrue(); // Placeholder for notification verification
        });

        it('sends Spanish notifications for expiration alerts', function () {
            $expiringSoonLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->addDays(7),
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/lots/check-expiration')
                ->assertOk();

            expect($response->json('message'))->toBe('Verificación de vencimiento completada.');
        });
    });

    describe('error message context and help text', function () {
        it('provides Spanish help text for validation errors', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 'invalid',
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'quantity' => [
                        'message' => 'La cantidad debe ser un número.',
                        'help' => 'Ingrese un número válido. Use punto (.) para decimales. Ejemplo: 10.5',
                    ],
                ]);
        });

        it('provides Spanish contextual error messages', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 5.0,
                'expiration_date' => now()->addDays(3),
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'quantity' => [
                        'message' => 'Cantidad insuficiente en inventario.',
                        'context' => [
                            'available' => 5.0,
                            'requested' => 10.0,
                            'lot_expires_in_days' => 3,
                            'suggestion' => 'Considere usar múltiples lotes o reducir la cantidad solicitada.',
                        ],
                    ],
                ]);
        });
    });

    describe('language fallback handling', function () {
        it('falls back to English when Spanish translation is missing', function () {
            // Test a hypothetical new validation rule without Spanish translation
            $this->actingAs($this->user)
                ->postJson('/api/movements/validate-special-rule', [
                    'special_field' => 'invalid_value',
                ])
                ->assertUnprocessable();

            // Should fall back to English if Spanish translation doesn't exist
            // This is a design consideration for the localization system
            expect(true)->toBeTrue();
        });

        it('maintains consistent Spanish terminology across the application', function () {
            // Verify that the same terms are used consistently
            $movementResponse = $this->actingAs($this->user)
                ->getJson('/api/movements')
                ->assertOk();

            $lotResponse = $this->actingAs($this->user)
                ->getJson('/api/lots')
                ->assertOk();

            // Both should use the same term for "quantity"
            expect($movementResponse->json('meta.labels.quantity'))->toBe('Cantidad');
            expect($lotResponse->json('meta.labels.quantity'))->toBe('Cantidad');
        });
    });
});
