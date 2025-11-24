<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchaseNumber = 'PUR-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6));
        $documentDate = fake()->dateTimeBetween('-30 days', 'now');
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxAmount = $subtotal * 0.16; // 16% tax
        $discountAmount = fake()->optional(0.3)->randomFloat(2, 10, $subtotal * 0.1) ?? 0;
        $shippingCost = fake()->optional(0.5)->randomFloat(2, 20, 200) ?? 0;
        $total = $subtotal + $taxAmount - $discountAmount + $shippingCost;

        return [
            'company_id' => \App\Models\Company::factory(),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'supplier_id' => \App\Models\Supplier::factory(),
            'purchase_number' => $purchaseNumber,
            'slug' => \Illuminate\Support\Str::slug($purchaseNumber),
            'document_type' => fake()->randomElement(['factura', 'ccf', 'ticket', 'otro']),
            'document_number' => fake()->unique()->numerify('DOC-######'),
            'document_date' => $documentDate,
            'due_date' => fake()->dateTimeBetween($documentDate, '+60 days'),
            'purchase_type' => fake()->randomElement(['efectivo', 'credito']),
            'payment_status' => 'pendiente',
            'payment_method' => fake()->randomElement(['transferencia', 'cheque', 'efectivo', 'credito', 'tarjeta']),
            'fund_source' => fake()->optional()->randomElement(['presupuesto_operativo', 'presupuesto_capital', 'donacion', 'proyecto']),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'status' => 'borrador',
            'approved_at' => null,
            'approved_by' => null,
            'received_at' => null,
            'received_by' => null,
            'notes' => fake()->optional()->sentence(),
            'admin_notes' => null,
            'attachments' => [],
            'is_active' => true,
            'active_at' => now(),
        ];
    }

    /**
     * Indicate that the purchase is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'borrador',
            'approved_at' => null,
            'approved_by' => null,
            'received_at' => null,
            'received_by' => null,
        ]);
    }

    /**
     * Indicate that the purchase is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendiente',
            'approved_at' => null,
            'approved_by' => null,
            'received_at' => null,
            'received_by' => null,
        ]);
    }

    /**
     * Indicate that the purchase is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprobado',
            'approved_at' => now()->subHours(2),
            'received_at' => null,
            'received_by' => null,
        ])->afterCreating(function ($purchase) {
            if (! $purchase->approved_by) {
                $purchase->approved_by = \App\Models\User::factory()->create([
                    'company_id' => $purchase->company_id,
                ])->id;
                $purchase->save();
            }
        });
    }

    /**
     * Indicate that the purchase is received.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'recibido',
            'approved_at' => now()->subDays(3),
            'received_at' => now()->subHours(1),
            'payment_status' => fake()->randomElement(['pendiente', 'parcial', 'pagado']),
        ])->afterCreating(function ($purchase) {
            if (! $purchase->approved_by) {
                $purchase->approved_by = \App\Models\User::factory()->create([
                    'company_id' => $purchase->company_id,
                ])->id;
            }
            if (! $purchase->received_by) {
                $purchase->received_by = \App\Models\User::factory()->create([
                    'company_id' => $purchase->company_id,
                ])->id;
            }
            $purchase->save();
        });
    }

    /**
     * Indicate that the purchase is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelado',
            'payment_status' => 'cancelado',
            'is_active' => false,
            'active_at' => null,
        ]);
    }

    /**
     * Indicate that the purchase is on credit.
     */
    public function onCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_type' => 'credito',
            'due_date' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the purchase is cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_type' => 'efectivo',
            'payment_status' => 'pagado',
        ]);
    }

    /**
     * Indicate that the purchase has a specific payment status.
     */
    public function paymentStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => $status,
        ]);
    }

    /**
     * Indicate that the purchase has details.
     */
    public function withDetails(int $count = 3): static
    {
        return $this->has(
            \App\Models\PurchaseDetail::factory()->count($count),
            'details'
        );
    }
}
