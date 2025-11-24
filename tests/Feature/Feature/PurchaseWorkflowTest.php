<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id]);

    $unitOfMeasure = UnitOfMeasure::factory()->create();
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'unit_of_measure_id' => $unitOfMeasure->id,
    ]);

    $this->actingAs($this->user);
});

test('can create a purchase in draft status', function () {
    $purchaseData = [
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'document_type' => 'factura',
        'document_number' => 'FAC-001',
        'document_date' => now()->format('Y-m-d'),
        'purchase_type' => 'efectivo',
        'shipping_cost' => 10.00,
        'details' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 5.00,
                'discount_percentage' => 0,
                'tax_percentage' => 13,
            ],
        ],
    ];

    $purchase = Purchase::create([
        'company_id' => $this->company->id,
        'warehouse_id' => $purchaseData['warehouse_id'],
        'supplier_id' => $purchaseData['supplier_id'],
        'document_type' => $purchaseData['document_type'],
        'document_number' => $purchaseData['document_number'],
        'document_date' => $purchaseData['document_date'],
        'purchase_type' => $purchaseData['purchase_type'],
        'shipping_cost' => $purchaseData['shipping_cost'],
        'status' => 'borrador',
    ]);

    foreach ($purchaseData['details'] as $detail) {
        PurchaseDetail::create([
            'purchase_id' => $purchase->id,
            'product_id' => $detail['product_id'],
            'quantity' => $detail['quantity'],
            'unit_cost' => $detail['unit_cost'],
            'discount_percentage' => $detail['discount_percentage'],
            'tax_percentage' => $detail['tax_percentage'],
        ]);
    }

    $purchase->calculateTotals();

    expect($purchase->status)->toBe('borrador')
        ->and($purchase->purchase_number)->toContain('PUR-')
        ->and($purchase->slug)->not->toBeNull()
        ->and($purchase->subtotal)->toBe(50.00)
        ->and($purchase->tax_amount)->toBe(6.50)
        ->and($purchase->total)->toBe(66.50);
});

test('can approve a pending purchase', function () {
    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'pendiente',
    ]);

    $result = $purchase->approve($this->user->id);

    expect($result)->toBeTrue()
        ->and($purchase->status)->toBe('aprobado')
        ->and($purchase->approved_by)->toBe($this->user->id)
        ->and($purchase->approved_at)->not->toBeNull();
});

test('cannot approve a purchase that is not pending', function () {
    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'borrador',
    ]);

    $result = $purchase->approve($this->user->id);

    expect($result)->toBeFalse()
        ->and($purchase->status)->toBe('borrador');
});

test('can receive an approved purchase and create inventory movements', function () {
    // Create movement reason
    MovementReason::factory()->create([
        'code' => 'PURCH_RCV',
        'name' => 'Recepción de Compra',
        'category' => 'inbound',
        'movement_type' => 'in',
    ]);

    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'aprobado',
    ]);

    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => 5.00,
    ]);

    $result = $purchase->receive($this->user->id);

    $purchase->refresh();

    expect($result)->toBeTrue()
        ->and($purchase->status)->toBe('recibido')
        ->and($purchase->received_by)->toBe($this->user->id)
        ->and($purchase->received_at)->not->toBeNull();

    // Verify inventory movement was created
    $movement = InventoryMovement::where('purchase_id', $purchase->id)->first();

    expect($movement)->not->toBeNull()
        ->and($movement->warehouse_id)->toBe($this->warehouse->id)
        ->and($movement->product_id)->toBe($this->product->id)
        ->and($movement->movement_type)->toBe('in')
        ->and($movement->quantity_in)->toBe(10.0)
        ->and($movement->quantity_out)->toBe(0.0)
        ->and($movement->balance_quantity)->toBe(10.0)
        ->and($movement->unit_cost)->toBe(5.00);
});

test('cannot receive a purchase that is not approved', function () {
    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'pendiente',
    ]);

    $result = $purchase->receive($this->user->id);

    expect($result)->toBeFalse()
        ->and($purchase->status)->toBe('pendiente');
});

test('can cancel a draft or pending purchase', function () {
    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'pendiente',
    ]);

    $result = $purchase->cancel();

    expect($result)->toBeTrue()
        ->and($purchase->status)->toBe('cancelado');
});

test('cannot cancel a received purchase', function () {
    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'recibido',
    ]);

    $result = $purchase->cancel();

    expect($result)->toBeFalse()
        ->and($purchase->status)->toBe('recibido');
});

test('inventory movements accumulate correctly for multiple purchases', function () {
    // Create movement reason
    MovementReason::factory()->create([
        'code' => 'PURCH_RCV',
        'name' => 'Recepción de Compra',
        'category' => 'inbound',
        'movement_type' => 'in',
    ]);

    // First purchase
    $purchase1 = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'aprobado',
    ]);

    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase1->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => 5.00,
    ]);

    $purchase1->receive($this->user->id);

    // Verify first balance
    $movement1 = InventoryMovement::where('purchase_id', $purchase1->id)->first();
    expect($movement1->balance_quantity)->toBe(10.0);

    // Second purchase
    $purchase2 = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'aprobado',
    ]);

    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase2->id,
        'product_id' => $this->product->id,
        'quantity' => 15,
        'unit_cost' => 5.50,
    ]);

    $purchase2->receive($this->user->id);

    // Verify accumulated balance
    $movement2 = InventoryMovement::where('purchase_id', $purchase2->id)->first();
    expect($movement2->balance_quantity)->toBe(25.0); // 10 + 15
});

test('purchase calculates totals correctly with tax and discount', function () {
    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'shipping_cost' => 10.00,
    ]);

    // Product 1: 10 units @ $5.00, 10% discount, 13% tax
    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => 5.00,
        'discount_percentage' => 10,
        'tax_percentage' => 13,
    ]);

    $purchase->calculateTotals();

    $detail = $purchase->details->first();

    // Subtotal: 10 * 5.00 = 50.00
    // Discount: 50.00 * 0.10 = 5.00
    // Taxable: 50.00 - 5.00 = 45.00
    // Tax: 45.00 * 0.13 = 5.85
    // Detail Total: 50.00 - 5.00 + 5.85 = 50.85

    expect($detail->subtotal)->toBe(50.00)
        ->and($detail->discount_amount)->toBe(5.00)
        ->and($detail->tax_amount)->toBe(5.85)
        ->and($detail->total)->toBe(50.85);

    // Purchase Total: 50.85 + 10.00 shipping = 60.85
    expect($purchase->total)->toBe(60.85);
});

test('purchase with multiple products calculates correctly', function () {
    $product2 = Product::factory()->create([
        'company_id' => $this->company->id,
        'unit_of_measure_id' => $this->product->unit_of_measure_id,
    ]);

    $purchase = Purchase::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'shipping_cost' => 20.00,
    ]);

    // Product 1: 10 units @ $5.00, no discount, 13% tax
    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => 5.00,
        'discount_percentage' => 0,
        'tax_percentage' => 13,
    ]);

    // Product 2: 5 units @ $10.00, 5% discount, 13% tax
    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $product2->id,
        'quantity' => 5,
        'unit_cost' => 10.00,
        'discount_percentage' => 5,
        'tax_percentage' => 13,
    ]);

    $purchase->calculateTotals();

    // Product 1: subtotal=50, discount=0, tax=6.50, total=56.50
    // Product 2: subtotal=50, discount=2.50, tax=6.175, total=53.675
    // Purchase: subtotal=100, discount=2.50, tax=12.675, shipping=20, total=130.175

    expect($purchase->subtotal)->toBe(100.00)
        ->and($purchase->discount_amount)->toBe(2.50)
        ->and($purchase->tax_amount)->toBe(12.68) // rounded
        ->and($purchase->total)->toBe(130.18); // rounded
});
