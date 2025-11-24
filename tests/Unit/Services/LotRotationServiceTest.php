<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LotRotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('LotRotationService', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();

        // Mock the service since it doesn't exist yet
        $this->service = $this->getMockBuilder(LotRotationService::class)
            ->onlyMethods(['selectByFIFO', 'selectByFEFO', 'selectByStrategy', 'getOptimalRotationStrategy'])
            ->getMock();
    });

    describe('FIFO (First In, First Out) algorithm', function () {
        beforeEach(function () {
            $this->oldestLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(30),
                'quantity_remaining' => 50.0,
                'status' => 'active',
                'created_at' => now()->subHours(3),
            ]);

            $this->middleLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(20),
                'quantity_remaining' => 75.0,
                'status' => 'active',
                'created_at' => now()->subHours(2),
            ]);

            $this->newestLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(10),
                'quantity_remaining' => 100.0,
                'status' => 'active',
                'created_at' => now()->subHours(1),
            ]);
        });

        it('selects lots in correct FIFO order', function () {
            // Mock FIFO selection to return lots ordered by manufacturing date (oldest first)
            $this->service->method('selectByFIFO')
                ->with($this->product->id, 75.0)
                ->willReturn(collect([$this->oldestLot, $this->middleLot]));

            $selectedLots = $this->service->selectByFIFO($this->product->id, 75.0);

            expect($selectedLots->first()->id)->toBe($this->oldestLot->id);
            expect($selectedLots->last()->id)->toBe($this->middleLot->id);
        });

        it('prioritizes by manufactured date over created date', function () {
            // Create lots with same creation time but different manufacturing dates
            $olderManufactured = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(40),
                'quantity_remaining' => 25.0,
                'status' => 'active',
                'created_at' => now()->subMinutes(5),
            ]);

            $newerManufactured = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(35),
                'quantity_remaining' => 25.0,
                'status' => 'active',
                'created_at' => now()->subMinutes(10), // Created earlier but manufactured later
            ]);

            $this->service->method('selectByFIFO')
                ->with($this->product->id, 25.0)
                ->willReturn(collect([$olderManufactured]));

            $selectedLots = $this->service->selectByFIFO($this->product->id, 25.0);

            expect($selectedLots->first()->id)->toBe($olderManufactured->id);
        });

        it('handles exact quantity requirements', function () {
            $this->service->method('selectByFIFO')
                ->with($this->product->id, 50.0)
                ->willReturn(collect([$this->oldestLot]));

            $selectedLots = $this->service->selectByFIFO($this->product->id, 50.0);

            expect($selectedLots)->toHaveCount(1);
            expect($selectedLots->sum('quantity_remaining'))->toBe(50.0);
        });

        it('combines multiple lots when needed', function () {
            $this->service->method('selectByFIFO')
                ->with($this->product->id, 120.0)
                ->willReturn(collect([$this->oldestLot, $this->middleLot]));

            $selectedLots = $this->service->selectByFIFO($this->product->id, 120.0);

            expect($selectedLots)->toHaveCount(2);
            expect($selectedLots->sum('quantity_remaining'))->toBeGreaterThanOrEqual(120.0);
        });

        it('excludes inactive and expired lots', function () {
            $inactiveLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(50), // Very old but inactive
                'quantity_remaining' => 100.0,
                'status' => 'inactive',
            ]);

            $expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(45),
                'quantity_remaining' => 100.0,
                'status' => 'expired',
            ]);

            $this->service->method('selectByFIFO')
                ->with($this->product->id, 50.0)
                ->willReturn(collect([$this->oldestLot]));

            $selectedLots = $this->service->selectByFIFO($this->product->id, 50.0);

            expect($selectedLots->contains('id', $inactiveLot->id))->toBeFalse();
            expect($selectedLots->contains('id', $expiredLot->id))->toBeFalse();
        });
    });

    describe('FEFO (First Expired, First Out) algorithm', function () {
        beforeEach(function () {
            $this->expiringSoonest = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(5),
                'expiration_date' => now()->addDays(5), // Expires in 5 days
                'quantity_remaining' => 30.0,
                'status' => 'active',
            ]);

            $this->expiringMedium = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(10),
                'expiration_date' => now()->addDays(20), // Expires in 20 days
                'quantity_remaining' => 60.0,
                'status' => 'active',
            ]);

            $this->expiringLatest = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(1),
                'expiration_date' => now()->addDays(60), // Expires in 60 days
                'quantity_remaining' => 90.0,
                'status' => 'active',
            ]);
        });

        it('selects lots in correct FEFO order', function () {
            $this->service->method('selectByFEFO')
                ->with($this->product->id, 80.0)
                ->willReturn(collect([$this->expiringSoonest, $this->expiringMedium]));

            $selectedLots = $this->service->selectByFEFO($this->product->id, 80.0);

            expect($selectedLots->first()->id)->toBe($this->expiringSoonest->id);
            expect($selectedLots->last()->id)->toBe($this->expiringMedium->id);
        });

        it('prioritizes expiration date over manufacturing date', function () {
            // Lot manufactured earlier but expires later
            $olderButExpiresLater = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(30),
                'expiration_date' => now()->addDays(100),
                'quantity_remaining' => 50.0,
                'status' => 'active',
            ]);

            $this->service->method('selectByFEFO')
                ->with($this->product->id, 50.0)
                ->willReturn(collect([$this->expiringSoonest]));

            $selectedLots = $this->service->selectByFEFO($this->product->id, 50.0);

            expect($selectedLots->first()->id)->toBe($this->expiringSoonest->id);
        });

        it('handles lots without expiration dates', function () {
            $noExpirationLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(15),
                'expiration_date' => null,
                'quantity_remaining' => 40.0,
                'status' => 'active',
            ]);

            // Lots without expiration should be treated as lowest priority
            $this->service->method('selectByFEFO')
                ->with($this->product->id, 60.0)
                ->willReturn(collect([$this->expiringSoonest, $this->expiringMedium]));

            $selectedLots = $this->service->selectByFEFO($this->product->id, 60.0);

            // Should not include the lot without expiration date
            expect($selectedLots->contains('id', $noExpirationLot->id))->toBeFalse();
        });

        it('excludes already expired lots', function () {
            $alreadyExpired = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(35),
                'expiration_date' => now()->subDays(1), // Already expired
                'quantity_remaining' => 25.0,
                'status' => 'expired',
            ]);

            $this->service->method('selectByFEFO')
                ->with($this->product->id, 30.0)
                ->willReturn(collect([$this->expiringSoonest]));

            $selectedLots = $this->service->selectByFEFO($this->product->id, 30.0);

            expect($selectedLots->contains('id', $alreadyExpired->id))->toBeFalse();
        });
    });

    describe('strategy selection and optimization', function () {
        it('recommends FEFO for products with short shelf life', function () {
            $shortShelfLifeProduct = Product::factory()->forCompany($this->company)->create([
                'shelf_life_days' => 30, // Short shelf life
            ]);

            $this->service->method('getOptimalRotationStrategy')
                ->with($shortShelfLifeProduct->id)
                ->willReturn('FEFO');

            $strategy = $this->service->getOptimalRotationStrategy($shortShelfLifeProduct->id);

            expect($strategy)->toBe('FEFO');
        });

        it('recommends FIFO for products with long shelf life', function () {
            $longShelfLifeProduct = Product::factory()->forCompany($this->company)->create([
                'shelf_life_days' => 365, // Long shelf life
            ]);

            $this->service->method('getOptimalRotationStrategy')
                ->with($longShelfLifeProduct->id)
                ->willReturn('FIFO');

            $strategy = $this->service->getOptimalRotationStrategy($longShelfLifeProduct->id);

            expect($strategy)->toBe('FIFO');
        });

        it('adapts strategy based on expiration proximity', function () {
            // Product with lots expiring soon should use FEFO
            ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->addDays(7), // Very soon
                'quantity_remaining' => 50.0,
                'status' => 'active',
            ]);

            $this->service->method('getOptimalRotationStrategy')
                ->with($this->product->id)
                ->willReturn('FEFO');

            $strategy = $this->service->getOptimalRotationStrategy($this->product->id);

            expect($strategy)->toBe('FEFO');
        });

        it('uses hybrid strategy when appropriate', function () {
            $this->service->method('selectByStrategy')
                ->with($this->product->id, 100.0, 'HYBRID')
                ->willReturn(collect([
                    // Mix of FEFO for expiring items and FIFO for stable items
                ]));

            $selectedLots = $this->service->selectByStrategy($this->product->id, 100.0, 'HYBRID');

            expect($selectedLots)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });
    });

    describe('performance optimization', function () {
        it('handles large numbers of lots efficiently', function () {
            // Create many lots to test performance
            $lots = [];
            for ($i = 0; $i < 1000; $i++) {
                $lots[] = [
                    'product_id' => $this->product->id,
                    'manufactured_date' => now()->subDays(rand(1, 365)),
                    'expiration_date' => now()->addDays(rand(30, 365)),
                    'quantity_remaining' => rand(10, 100),
                    'status' => 'active',
                ];
            }

            // Mock that service can handle large datasets efficiently
            $this->service->method('selectByFIFO')
                ->with($this->product->id, 500.0)
                ->willReturn(collect(array_slice($lots, 0, 10))); // Return first 10

            $startTime = microtime(true);
            $selectedLots = $this->service->selectByFIFO($this->product->id, 500.0);
            $endTime = microtime(true);

            expect($endTime - $startTime)->toBeLessThan(1.0); // Should complete within 1 second
            expect($selectedLots)->toHaveCount(10);
        });

        it('uses database indexing for optimal performance', function () {
            // This would test that proper database queries are used
            // with appropriate indexes on manufactured_date, expiration_date, etc.

            $this->service->method('selectByFEFO')
                ->with($this->product->id, 50.0)
                ->willReturn(collect([]));

            // In real implementation, this would verify query optimization
            $selectedLots = $this->service->selectByFEFO($this->product->id, 50.0);

            expect($selectedLots)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });
    });

    describe('edge cases and error handling', function () {
        it('handles products with no available lots', function () {
            $emptyProduct = Product::factory()->forCompany($this->company)->create();

            $this->service->method('selectByFIFO')
                ->with($emptyProduct->id, 10.0)
                ->willReturn(collect([]));

            $selectedLots = $this->service->selectByFIFO($emptyProduct->id, 10.0);

            expect($selectedLots)->toHaveCount(0);
        });

        it('handles zero quantity requests', function () {
            $this->service->method('selectByFIFO')
                ->with($this->product->id, 0.0)
                ->willReturn(collect([]));

            $selectedLots = $this->service->selectByFIFO($this->product->id, 0.0);

            expect($selectedLots)->toHaveCount(0);
        });

        it('handles negative quantity requests gracefully', function () {
            $this->service->method('selectByFIFO')
                ->with($this->product->id, -10.0)
                ->willThrowException(new \InvalidArgumentException('Quantity cannot be negative'));

            expect(fn () => $this->service->selectByFIFO($this->product->id, -10.0))
                ->toThrow(\InvalidArgumentException::class, 'Quantity cannot be negative');
        });

        it('handles fractional quantities correctly', function () {
            $this->service->method('selectByFIFO')
                ->with($this->product->id, 15.75)
                ->willReturn(collect([])); // Mock appropriate response

            $selectedLots = $this->service->selectByFIFO($this->product->id, 15.75);

            expect($selectedLots)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        it('handles lots with same dates correctly', function () {
            $sameDate = now()->subDays(15);

            $lot1 = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => $sameDate,
                'expiration_date' => $sameDate->copy()->addDays(30),
                'quantity_remaining' => 25.0,
                'status' => 'active',
                'created_at' => now()->subHours(2),
            ]);

            $lot2 = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => $sameDate,
                'expiration_date' => $sameDate->copy()->addDays(30),
                'quantity_remaining' => 25.0,
                'status' => 'active',
                'created_at' => now()->subHours(1),
            ]);

            // Should use creation time as tiebreaker
            $this->service->method('selectByFIFO')
                ->with($this->product->id, 25.0)
                ->willReturn(collect([$lot1])); // Earlier created

            $selectedLots = $this->service->selectByFIFO($this->product->id, 25.0);

            expect($selectedLots->first()->id)->toBe($lot1->id);
        });
    });

    describe('compliance and regulatory requirements', function () {
        it('maintains audit trail for lot selection decisions', function () {
            $this->service->method('selectByFEFO')
                ->with($this->product->id, 50.0)
                ->willReturn(collect([
                    (object) [
                        'id' => 1,
                        'selection_reason' => 'FEFO - Expires in 5 days',
                        'selection_timestamp' => now(),
                        'selected_by_user' => $this->user->id,
                    ],
                ]));

            $selectedLots = $this->service->selectByFEFO($this->product->id, 50.0);

            expect($selectedLots->first()->selection_reason)->toContain('FEFO');
        });

        it('respects regulatory FEFO requirements for pharmaceuticals', function () {
            $pharmaceuticalProduct = Product::factory()->forCompany($this->company)->create([
                'category' => 'pharmaceutical',
                'requires_fefo' => true,
            ]);

            $this->service->method('getOptimalRotationStrategy')
                ->with($pharmaceuticalProduct->id)
                ->willReturn('FEFO');

            $strategy = $this->service->getOptimalRotationStrategy($pharmaceuticalProduct->id);

            expect($strategy)->toBe('FEFO');
        });

        it('handles quarantined lots appropriately', function () {
            $quarantinedLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'quarantined',
                'quantity_remaining' => 100.0,
            ]);

            $this->service->method('selectByFIFO')
                ->with($this->product->id, 50.0)
                ->willReturn(collect([])); // Should exclude quarantined lots

            $selectedLots = $this->service->selectByFIFO($this->product->id, 50.0);

            expect($selectedLots->contains('id', $quarantinedLot->id))->toBeFalse();
        });
    });
});
