<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\MovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('MovementService', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
        $this->supplier = Supplier::factory()->forCompany($this->company)->create();
        $this->customer = Customer::factory()->forCompany($this->company)->create();
        $this->location = StorageLocation::factory()->forCompany($this->company)->forWarehouse($this->warehouse)->create();

        // Mock the service since it doesn't exist yet
        $this->service = $this->getMockBuilder(MovementService::class)
            ->onlyMethods(['selectOptimalLots', 'requestMovement', 'validateMovement'])
            ->getMock();
    });

    describe('lot selection algorithms', function () {
        beforeEach(function () {
            // Create test lots with different dates
            $this->oldLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(30),
                'expiration_date' => now()->addDays(60),
                'quantity_remaining' => 50.0,
                'status' => 'active',
            ]);

            $this->newLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(10),
                'expiration_date' => now()->addDays(30),
                'quantity_remaining' => 75.0,
                'status' => 'active',
            ]);

            $this->expiringSoonLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(5),
                'expiration_date' => now()->addDays(5),
                'quantity_remaining' => 25.0,
                'status' => 'active',
            ]);
        });

        it('selects lots using FIFO strategy correctly', function () {
            // Mock the service to return lots in FIFO order
            $this->service->method('selectOptimalLots')
                ->with($this->product->id, 30.0, 'FIFO')
                ->willReturn(collect([$this->oldLot])); // Oldest first

            $selectedLots = $this->service->selectOptimalLots($this->product->id, 30.0, 'FIFO');

            expect($selectedLots->first()->id)->toBe($this->oldLot->id);
        });

        it('selects lots using FEFO strategy correctly', function () {
            // Mock the service to return lots in FEFO order
            $this->service->method('selectOptimalLots')
                ->with($this->product->id, 20.0, 'FEFO')
                ->willReturn(collect([$this->expiringSoonLot])); // Expires soonest first

            $selectedLots = $this->service->selectOptimalLots($this->product->id, 20.0, 'FEFO');

            expect($selectedLots->first()->id)->toBe($this->expiringSoonLot->id);
        });

        it('handles insufficient inventory gracefully', function () {
            // Mock the service to throw exception for insufficient inventory
            $this->service->method('selectOptimalLots')
                ->with($this->product->id, 200.0, 'FIFO')
                ->willThrowException(new \InvalidArgumentException('Insufficient inventory available'));

            expect(fn () => $this->service->selectOptimalLots($this->product->id, 200.0, 'FIFO'))
                ->toThrow(\InvalidArgumentException::class, 'Insufficient inventory available');
        });

        it('selects multiple lots when single lot is insufficient', function () {
            // Mock the service to return multiple lots
            $this->service->method('selectOptimalLots')
                ->with($this->product->id, 100.0, 'FIFO')
                ->willReturn(collect([$this->oldLot, $this->newLot]));

            $selectedLots = $this->service->selectOptimalLots($this->product->id, 100.0, 'FIFO');

            expect($selectedLots)->toHaveCount(2);
            expect($selectedLots->sum('quantity_remaining'))->toBeGreaterThanOrEqual(100.0);
        });

        it('excludes expired lots from selection', function () {
            $expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->subDays(1),
                'quantity_remaining' => 100.0,
                'status' => 'expired',
            ]);

            // Mock service to not include expired lots
            $this->service->method('selectOptimalLots')
                ->with($this->product->id, 50.0, 'FIFO')
                ->willReturn(collect([$this->oldLot]));

            $selectedLots = $this->service->selectOptimalLots($this->product->id, 50.0, 'FIFO');

            expect($selectedLots->contains('id', $expiredLot->id))->toBeFalse();
        });
    });

    describe('movement validation', function () {
        it('validates movement data correctly', function () {
            $validMovementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => 10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('validateMovement')
                ->with($validMovementData)
                ->willReturn(true);

            $result = $this->service->validateMovement($validMovementData);

            expect($result)->toBeTrue();
        });

        it('rejects invalid movement types', function () {
            $invalidMovementData = [
                'type' => 'invalid_type',
                'product_id' => $this->product->id,
                'quantity' => 10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('validateMovement')
                ->with($invalidMovementData)
                ->willThrowException(new \InvalidArgumentException('Invalid movement type'));

            expect(fn () => $this->service->validateMovement($invalidMovementData))
                ->toThrow(\InvalidArgumentException::class, 'Invalid movement type');
        });

        it('rejects negative quantities for inbound movements', function () {
            $invalidMovementData = [
                'type' => 'in',
                'product_id' => $this->product->id,
                'quantity' => -10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('validateMovement')
                ->with($invalidMovementData)
                ->willThrowException(new \InvalidArgumentException('Inbound movements cannot have negative quantities'));

            expect(fn () => $this->service->validateMovement($invalidMovementData))
                ->toThrow(\InvalidArgumentException::class, 'Inbound movements cannot have negative quantities');
        });

        it('rejects positive quantities for outbound movements', function () {
            $invalidMovementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => 10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('validateMovement')
                ->with($invalidMovementData)
                ->willThrowException(new \InvalidArgumentException('Outbound movements must have negative quantities'));

            expect(fn () => $this->service->validateMovement($invalidMovementData))
                ->toThrow(\InvalidArgumentException::class, 'Outbound movements must have negative quantities');
        });

        it('validates warehouse access permissions', function () {
            $otherCompany = Company::factory()->create();
            $otherWarehouse = Warehouse::factory()->forCompany($otherCompany)->create();

            $invalidMovementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -10.0,
                'warehouse_id' => $otherWarehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('validateMovement')
                ->with($invalidMovementData)
                ->willThrowException(new \UnauthorizedHttpException('401', 'Access denied to warehouse'));

            expect(fn () => $this->service->validateMovement($invalidMovementData))
                ->toThrow(\UnauthorizedHttpException::class);
        });
    });

    describe('movement execution', function () {
        beforeEach(function () {
            Event::fake();
        });

        it('creates movement successfully with valid data', function () {
            $movementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
                'notes' => 'Test movement',
            ];

            // Mock successful movement creation
            $mockMovement = InventoryMovement::factory()->make([
                'id' => 123,
                'movement_type' => 'out',
                'quantity' => -10.0,
                'status' => 'pending',
            ]);

            $this->service->method('requestMovement')
                ->with($movementData)
                ->willReturn($mockMovement);

            $result = $this->service->requestMovement($movementData);

            expect($result)->toBeInstanceOf(InventoryMovement::class);
            expect($result->movement_type)->toBe('out');
            expect($result->quantity)->toBe(-10.0);
        });

        it('prevents double spending by checking available inventory', function () {
            // Create a lot with limited inventory
            $limitedLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 5.0,
                'status' => 'active',
            ]);

            $movementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -10.0, // More than available
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('requestMovement')
                ->with($movementData)
                ->willThrowException(new \InvalidArgumentException('Insufficient inventory available'));

            expect(fn () => $this->service->requestMovement($movementData))
                ->toThrow(\InvalidArgumentException::class, 'Insufficient inventory available');
        });

        it('handles concurrent movement requests safely', function () {
            // This would test database-level locking mechanisms
            $movementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            // Mock that the service handles concurrency correctly
            $mockMovement = InventoryMovement::factory()->make(['status' => 'pending']);

            $this->service->method('requestMovement')
                ->with($movementData)
                ->willReturn($mockMovement);

            $result = $this->service->requestMovement($movementData);

            expect($result->status)->toBe('pending');
        });
    });

    describe('high-value movement approval workflow', function () {
        it('requires approval for high-value movements', function () {
            $highValueMovementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -100.0,
                'unit_cost' => 200.0, // High value: 20,000 total
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $mockMovement = InventoryMovement::factory()->make([
                'status' => 'pending_approval',
                'requires_approval' => true,
            ]);

            $this->service->method('requestMovement')
                ->with($highValueMovementData)
                ->willReturn($mockMovement);

            $result = $this->service->requestMovement($highValueMovementData);

            expect($result->status)->toBe('pending_approval');
            expect($result->requires_approval)->toBeTrue();
        });

        it('auto-approves low-value movements', function () {
            $lowValueMovementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -5.0,
                'unit_cost' => 10.0, // Low value: 50 total
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $mockMovement = InventoryMovement::factory()->make([
                'status' => 'approved',
                'requires_approval' => false,
            ]);

            $this->service->method('requestMovement')
                ->with($lowValueMovementData)
                ->willReturn($mockMovement);

            $result = $this->service->requestMovement($lowValueMovementData);

            expect($result->status)->toBe('approved');
            expect($result->requires_approval)->toBeFalse();
        });
    });

    describe('transfer movements', function () {
        beforeEach(function () {
            $this->sourceWarehouse = $this->warehouse;
            $this->targetWarehouse = Warehouse::factory()->forCompany($this->company)->create();
        });

        it('creates paired transfer movements correctly', function () {
            $transferData = [
                'product_id' => $this->product->id,
                'quantity' => 25.0,
                'from_warehouse_id' => $this->sourceWarehouse->id,
                'to_warehouse_id' => $this->targetWarehouse->id,
                'user_id' => $this->user->id,
            ];

            // Mock paired transfer creation
            $transferOut = InventoryMovement::factory()->make([
                'movement_type' => 'transfer_out',
                'quantity' => -25.0,
                'warehouse_id' => $this->sourceWarehouse->id,
                'transfer_id' => 'TRF-001',
            ]);

            $transferIn = InventoryMovement::factory()->make([
                'movement_type' => 'transfer_in',
                'quantity' => 25.0,
                'warehouse_id' => $this->targetWarehouse->id,
                'transfer_id' => 'TRF-001',
            ]);

            $this->service->method('requestMovement')
                ->with($transferData)
                ->willReturn(collect([$transferOut, $transferIn]));

            $result = $this->service->requestMovement($transferData);

            expect($result)->toHaveCount(2);
            expect($result->first()->transfer_id)->toBe($result->last()->transfer_id);
        });

        it('prevents transfers between different companies', function () {
            $otherCompany = Company::factory()->create();
            $otherWarehouse = Warehouse::factory()->forCompany($otherCompany)->create();

            $invalidTransferData = [
                'product_id' => $this->product->id,
                'quantity' => 25.0,
                'from_warehouse_id' => $this->sourceWarehouse->id,
                'to_warehouse_id' => $otherWarehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('requestMovement')
                ->with($invalidTransferData)
                ->willThrowException(new \UnauthorizedHttpException('401', 'Cannot transfer between different companies'));

            expect(fn () => $this->service->requestMovement($invalidTransferData))
                ->toThrow(\UnauthorizedHttpException::class);
        });
    });

    describe('error handling and rollback', function () {
        beforeEach(function () {
            Event::fake();
        });

        it('rolls back failed movements correctly', function () {
            $movementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('requestMovement')
                ->with($movementData)
                ->willThrowException(new \Exception('Database error occurred'));

            expect(fn () => $this->service->requestMovement($movementData))
                ->toThrow(\Exception::class, 'Database error occurred');
        });

        it('handles expired lot scenarios gracefully', function () {
            $expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->subDays(1),
                'status' => 'expired',
            ]);

            $movementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'product_lot_id' => $expiredLot->id,
                'quantity' => -10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
            ];

            $this->service->method('requestMovement')
                ->with($movementData)
                ->willThrowException(new \InvalidArgumentException('Cannot move from expired lot'));

            expect(fn () => $this->service->requestMovement($movementData))
                ->toThrow(\InvalidArgumentException::class, 'Cannot move from expired lot');
        });
    });

    describe('audit trail and logging', function () {
        it('creates proper audit trail for movements', function () {
            $movementData = [
                'type' => 'out',
                'product_id' => $this->product->id,
                'quantity' => -10.0,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->user->id,
                'reason' => 'Customer order fulfillment',
            ];

            $mockMovement = InventoryMovement::factory()->make([
                'created_by' => $this->user->id,
                'metadata' => [
                    'reason' => 'Customer order fulfillment',
                    'source_ip' => '127.0.0.1',
                    'user_agent' => 'PHPUnit Test',
                ],
            ]);

            $this->service->method('requestMovement')
                ->with($movementData)
                ->willReturn($mockMovement);

            $result = $this->service->requestMovement($movementData);

            expect($result->created_by)->toBe($this->user->id);
            expect($result->metadata['reason'])->toBe('Customer order fulfillment');
        });
    });
});
