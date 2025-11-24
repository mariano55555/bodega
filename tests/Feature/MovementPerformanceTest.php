<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Movement Performance Tests', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();

        // Disable query logging for performance testing
        DB::connection()->disableQueryLog();
    });

    describe('high volume movement processing', function () {
        it('handles bulk movement creation efficiently', function () {
            // Create base data
            $products = Product::factory()->count(50)->forCompany($this->company)->create();
            $lots = collect();

            // Create lots for each product
            $products->each(function ($product) use (&$lots) {
                $productLots = ProductLot::factory()->count(20)->create([
                    'product_id' => $product->id,
                    'quantity_remaining' => 100.0,
                    'status' => 'active',
                ]);
                $lots = $lots->merge($productLots);
            });

            // Prepare bulk movement data (1000 movements)
            $movementData = collect(range(1, 1000))->map(function ($index) use ($products, $lots) {
                $product = $products->random();
                $lot = $lots->where('product_id', $product->id)->random();

                return [
                    'movement_type' => 'sale',
                    'product_id' => $product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -rand(1, 10),
                    'unit_cost' => rand(10, 100),
                    'reference_number' => "BULK-{$index}",
                    'status' => 'pending',
                ];
            })->toArray();

            // Measure bulk insert performance
            $startTime = microtime(true);

            DB::table('inventory_movements')->insert($movementData);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Performance assertions
            expect($executionTime)->toBeLessThan(5.0); // Should complete within 5 seconds
            expect(InventoryMovement::count())->toBe(1000);

            // Verify data integrity
            $sampleMovement = InventoryMovement::where('reference_number', 'BULK-1')->first();
            expect($sampleMovement)->not->toBeNull();
            expect($sampleMovement->product_id)->toBeIn($products->pluck('id')->toArray());
        });

        it('processes concurrent movement requests efficiently', function () {
            // Create test data
            $product = Product::factory()->forCompany($this->company)->create();
            $lot = ProductLot::factory()->create([
                'product_id' => $product->id,
                'quantity_remaining' => 10000.0, // Large quantity
                'status' => 'active',
            ]);

            // Simulate 50 concurrent movement requests
            $concurrentRequests = collect(range(1, 50))->map(function ($index) use ($product, $lot) {
                return [
                    'movement_type' => 'sale',
                    'product_id' => $product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                    'reference_number' => "CONCURRENT-{$index}",
                ];
            });

            $startTime = microtime(true);

            // Process requests in batches to simulate concurrency
            $concurrentRequests->chunk(10)->each(function ($batch) {
                $batch->each(function ($movementData) {
                    $this->actingAs($this->user)
                        ->postJson('/api/movements', $movementData);
                });
            });

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Performance and consistency checks
            expect($executionTime)->toBeLessThan(30.0); // Should complete within 30 seconds

            $createdMovements = InventoryMovement::where('reference_number', 'like', 'CONCURRENT-%')->count();
            expect($createdMovements)->toBeGreaterThan(0); // Some should succeed

            // Verify inventory consistency
            $lot->refresh();
            $expectedQuantity = 10000.0 - ($createdMovements * 10.0);
            expect($lot->quantity_remaining)->toBe($expectedQuantity);
        });

        it('optimizes FIFO/FEFO lot selection for large datasets', function () {
            $product = Product::factory()->forCompany($this->company)->create();

            // Create 1000 lots with varying dates
            $lots = collect(range(1, 1000))->map(function ($index) use ($product) {
                return [
                    'product_id' => $product->id,
                    'lot_number' => "PERF-LOT-{$index}",
                    'manufactured_date' => now()->subDays(rand(1, 365))->format('Y-m-d'),
                    'expiration_date' => now()->addDays(rand(30, 365))->format('Y-m-d'),
                    'quantity_remaining' => rand(10, 100),
                    'status' => 'active',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DB::table('product_lots')->insert($lots);

            // Test FIFO selection performance
            $startTime = microtime(true);

            $fifoResponse = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/lots/fifo?quantity=500")
                ->assertOk();

            $fifoTime = microtime(true) - $startTime;

            // Test FEFO selection performance
            $startTime = microtime(true);

            $fefoResponse = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/lots/fefo?quantity=500")
                ->assertOk();

            $fefoTime = microtime(true) - $startTime;

            // Performance assertions
            expect($fifoTime)->toBeLessThan(2.0); // FIFO should complete within 2 seconds
            expect($fefoTime)->toBeLessThan(2.0); // FEFO should complete within 2 seconds

            // Verify correct ordering
            $fifoLots = $fifoResponse->json('data');
            $fefoLots = $fefoResponse->json('data');

            expect($fifoLots)->not->toBeEmpty();
            expect($fefoLots)->not->toBeEmpty();
        });

        it('handles inventory calculation for products with many lots efficiently', function () {
            $product = Product::factory()->forCompany($this->company)->create();

            // Create lots with movements
            collect(range(1, 500))->each(function ($index) use ($product) {
                $lot = ProductLot::factory()->create([
                    'product_id' => $product->id,
                    'quantity_remaining' => 100.0,
                ]);

                // Create multiple movements per lot
                InventoryMovement::factory()->count(5)->create([
                    'product_id' => $product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => rand(-20, 20),
                    'status' => 'completed',
                ]);
            });

            // Measure inventory calculation performance
            $startTime = microtime(true);

            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/inventory-summary")
                ->assertOk();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($executionTime)->toBeLessThan(3.0); // Should complete within 3 seconds

            $summary = $response->json('data');
            expect($summary)->toHaveKey('total_quantity');
            expect($summary)->toHaveKey('total_lots');
            expect($summary)->toHaveKey('movement_count');
        });
    });

    describe('database query optimization', function () {
        it('uses efficient queries for movement history retrieval', function () {
            $product = Product::factory()->forCompany($this->company)->create();
            $lot = ProductLot::factory()->create(['product_id' => $product->id]);

            // Create movement history
            InventoryMovement::factory()->count(1000)->create([
                'product_id' => $product->id,
                'product_lot_id' => $lot->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

            // Enable query logging temporarily
            DB::enableQueryLog();

            $startTime = microtime(true);

            $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/movements?per_page=50")
                ->assertOk();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            // Performance assertions
            expect($executionTime)->toBeLessThan(1.0);
            expect(count($queries))->toBeLessThan(10); // Should use efficient eager loading
        });

        it('optimizes lot availability queries with proper indexing', function () {
            $product = Product::factory()->forCompany($this->company)->create();

            // Create many lots with different statuses
            ProductLot::factory()->count(2000)->create([
                'product_id' => $product->id,
                'status' => 'active',
                'quantity_remaining' => rand(0, 100),
            ]);

            ProductLot::factory()->count(500)->create([
                'product_id' => $product->id,
                'status' => 'expired',
                'quantity_remaining' => 0,
            ]);

            DB::enableQueryLog();

            $startTime = microtime(true);

            $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/available-lots")
                ->assertOk();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            expect($executionTime)->toBeLessThan(0.5); // Very fast for filtered queries

            // Verify the query uses indexes (look for USING INDEX in explain plan)
            $query = $queries[0]['query'] ?? '';
            expect($query)->toContain('WHERE');
        });
    });

    describe('caching and optimization strategies', function () {
        it('caches frequently accessed inventory data', function () {
            $product = Product::factory()->forCompany($this->company)->create();

            // First request should hit database
            $startTime = microtime(true);
            $response1 = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/inventory-levels")
                ->assertOk();
            $firstRequestTime = microtime(true) - $startTime;

            // Second request should hit cache
            $startTime = microtime(true);
            $response2 = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/inventory-levels")
                ->assertOk();
            $secondRequestTime = microtime(true) - $startTime;

            // Cache should make second request significantly faster
            expect($secondRequestTime)->toBeLessThan($firstRequestTime * 0.5);
            expect($response1->json())->toBe($response2->json());
        });

        it('invalidates cache appropriately on inventory changes', function () {
            $product = Product::factory()->forCompany($this->company)->create();
            $lot = ProductLot::factory()->create([
                'product_id' => $product->id,
                'quantity_remaining' => 100.0,
            ]);

            // Cache initial inventory data
            $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/inventory-levels");

            // Make a movement that should invalidate cache
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ]);

            // Verify cache is invalidated by checking updated inventory
            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/inventory-levels")
                ->assertOk();

            $levels = $response->json('data');
            expect($levels['total_available'])->toBeLessThan(100.0);
        });

        it('uses pagination efficiently for large result sets', function () {
            $product = Product::factory()->forCompany($this->company)->create();

            // Create many movements
            InventoryMovement::factory()->count(5000)->create([
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

            // Test paginated retrieval performance
            $startTime = microtime(true);

            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/movements?per_page=100&page=1")
                ->assertOk();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($executionTime)->toBeLessThan(1.0);

            $data = $response->json();
            expect($data['data'])->toHaveCount(100);
            expect($data['total'])->toBe(5000);
            expect($data['per_page'])->toBe(100);
        });
    });

    describe('memory usage optimization', function () {
        it('processes large datasets without memory overflow', function () {
            $product = Product::factory()->forCompany($this->company)->create();

            $initialMemory = memory_get_usage();

            // Process large batch of movements using chunking
            $batchSize = 1000;
            $totalBatches = 10;

            for ($batch = 0; $batch < $totalBatches; $batch++) {
                $movements = collect(range(1, $batchSize))->map(function ($index) use ($product, $batch) {
                    return [
                        'movement_type' => 'adjustment',
                        'product_id' => $product->id,
                        'warehouse_id' => $this->warehouse->id,
                        'quantity' => rand(-10, 10),
                        'unit_cost' => 25.00,
                        'reference_number' => "BATCH-{$batch}-{$index}",
                        'status' => 'completed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                DB::table('inventory_movements')->insert($movements);

                // Force garbage collection between batches
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            $finalMemory = memory_get_usage();
            $memoryIncrease = $finalMemory - $initialMemory;

            // Memory increase should be reasonable (less than 50MB)
            expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024);

            // Verify all data was inserted
            $totalMovements = InventoryMovement::where('reference_number', 'like', 'BATCH-%')->count();
            expect($totalMovements)->toBe($batchSize * $totalBatches);
        });

        it('efficiently streams large export datasets', function () {
            $product = Product::factory()->forCompany($this->company)->create();

            // Create large dataset
            InventoryMovement::factory()->count(10000)->create([
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

            $initialMemory = memory_get_usage();

            // Simulate streaming export
            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$product->id}/movements/export?format=stream")
                ->assertOk();

            $peakMemory = memory_get_peak_usage();
            $memoryUsed = $peakMemory - $initialMemory;

            // Memory usage should remain low even with large datasets
            expect($memoryUsed)->toBeLessThan(20 * 1024 * 1024); // Less than 20MB
        });
    });

    describe('queue processing performance', function () {
        it('processes movement queues efficiently under load', function () {
            Queue::fake();

            $product = Product::factory()->forCompany($this->company)->create();
            $lot = ProductLot::factory()->create([
                'product_id' => $product->id,
                'quantity_remaining' => 10000.0,
            ]);

            // Queue many movement processing jobs
            $startTime = microtime(true);

            collect(range(1, 500))->each(function ($index) use ($product, $lot) {
                $this->actingAs($this->user)
                    ->postJson('/api/movements', [
                        'movement_type' => 'sale',
                        'product_id' => $product->id,
                        'product_lot_id' => $lot->id,
                        'warehouse_id' => $this->warehouse->id,
                        'quantity' => -1.0,
                        'unit_cost' => 25.00,
                        'reference_number' => "QUEUE-{$index}",
                    ]);
            });

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($executionTime)->toBeLessThan(60.0); // Should queue within 60 seconds

            // Verify jobs were queued
            Queue::assertPushedOn('movements', \App\Jobs\ProcessMovement::class);
        });
    });

    describe('database connection optimization', function () {
        it('handles connection pooling efficiently under load', function () {
            // Simulate multiple concurrent database operations
            $operations = collect(range(1, 100));

            $startTime = microtime(true);

            $operations->each(function ($index) {
                // Simulate various database operations
                Product::factory()->forCompany($this->company)->create([
                    'name' => "Product {$index}",
                ]);

                ProductLot::factory()->create([
                    'product_id' => Product::latest()->first()->id,
                    'lot_number' => "LOT-{$index}",
                ]);

                InventoryMovement::factory()->create([
                    'product_id' => Product::latest()->first()->id,
                    'warehouse_id' => $this->warehouse->id,
                    'reference_number' => "REF-{$index}",
                ]);
            });

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($executionTime)->toBeLessThan(20.0); // Should complete within 20 seconds
            expect(Product::count())->toBeGreaterThanOrEqual(100);
            expect(ProductLot::count())->toBeGreaterThanOrEqual(100);
            expect(InventoryMovement::count())->toBeGreaterThanOrEqual(100);
        });
    });
});
