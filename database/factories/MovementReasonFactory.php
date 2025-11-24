<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MovementReason>
 */
class MovementReasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['inbound', 'outbound', 'transfer', 'adjustment', 'disposal'];
        $category = $this->faker->randomElement($categories);

        $movementTypes = match ($category) {
            'inbound' => ['in'],
            'outbound' => ['out'],
            'transfer' => ['transfer'],
            'adjustment' => ['in', 'out'],
            'disposal' => ['out'],
            default => ['in', 'out'],
        };

        $name = $this->generateReasonName($category);
        $code = $this->generateReasonCode($category);

        return [
            'code' => $code,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->generateDescription($category),
            'category' => $category,
            'movement_type' => $this->faker->randomElement($movementTypes),
            'requires_approval' => $this->faker->boolean(30), // 30% chance
            'requires_documentation' => $this->faker->boolean(40), // 40% chance
            'approval_threshold' => $this->faker->optional(0.3)->randomFloat(2, 100, 10000),
            'required_fields' => $this->generateRequiredFields($category),
            'validation_rules' => $this->generateValidationRules($category),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'is_active' => true,
            'active_at' => now(),
            'created_by' => $this->faker->optional(0.8)->randomElement([
                User::factory(),
                fn () => User::inRandomOrder()->first()?->id,
            ]),
        ];
    }

    /**
     * Generate a reason name based on category.
     */
    private function generateReasonName(string $category): string
    {
        $names = match ($category) {
            'inbound' => [
                'Compra a Proveedor',
                'Devolución de Cliente',
                'Producción Interna',
                'Transferencia de Entrada',
                'Donación Recibida',
                'Muestra Promocional',
            ],
            'outbound' => [
                'Venta a Cliente',
                'Devolución a Proveedor',
                'Consumo Interno',
                'Transferencia de Salida',
                'Donación Entregada',
                'Muestra Enviada',
                'Merma Natural',
            ],
            'transfer' => [
                'Transferencia entre Almacenes',
                'Reubicación de Producto',
                'Optimización de Espacio',
                'Consolidación de Stock',
            ],
            'adjustment' => [
                'Ajuste por Inventario Físico',
                'Corrección de Error',
                'Diferencia de Conteo',
                'Actualización de Sistema',
                'Reconciliación',
            ],
            'disposal' => [
                'Producto Vencido',
                'Producto Dañado',
                'Destrucción por Calidad',
                'Disposición Sanitaria',
                'Pérdida por Robo',
            ],
            default => ['Motivo General'],
        };

        return $this->faker->randomElement($names);
    }

    /**
     * Generate a reason code based on category.
     */
    private function generateReasonCode(string $category): string
    {
        $prefixes = match ($category) {
            'inbound' => ['ENT', 'ING', 'REC'],
            'outbound' => ['SAL', 'EGR', 'OUT'],
            'transfer' => ['TRF', 'MOV', 'REU'],
            'adjustment' => ['AJU', 'COR', 'INV'],
            'disposal' => ['DIS', 'ELI', 'DES'],
            default => ['GEN'],
        };

        $prefix = $this->faker->randomElement($prefixes);
        $number = $this->faker->numberBetween(100, 999);

        return "{$prefix}-{$number}";
    }

    /**
     * Generate a description based on category.
     */
    private function generateDescription(string $category): string
    {
        $descriptions = match ($category) {
            'inbound' => [
                'Movimiento de entrada de mercancía al almacén',
                'Recepción de productos desde proveedor externo',
                'Ingreso de productos por devolución de cliente',
                'Entrada de productos por producción interna',
            ],
            'outbound' => [
                'Movimiento de salida de mercancía del almacén',
                'Entrega de productos a cliente final',
                'Salida de productos por devolución a proveedor',
                'Egreso de productos por consumo interno',
            ],
            'transfer' => [
                'Movimiento entre ubicaciones de almacén',
                'Transferencia de productos entre almacenes',
                'Reubicación interna de mercancía',
            ],
            'adjustment' => [
                'Ajuste de inventario por diferencias físicas',
                'Corrección de cantidades en sistema',
                'Actualización por conteo físico',
            ],
            'disposal' => [
                'Disposición de productos no aptos para venta',
                'Eliminación de mercancía vencida o dañada',
                'Destrucción por razones sanitarias',
            ],
            default => ['Motivo general de movimiento de inventario'],
        };

        return $this->faker->randomElement($descriptions);
    }

    /**
     * Generate required fields based on category.
     */
    private function generateRequiredFields(string $category): ?array
    {
        $fields = match ($category) {
            'inbound' => ['supplier_id', 'purchase_order', 'invoice_number'],
            'outbound' => ['customer_id', 'sales_order', 'delivery_note'],
            'transfer' => ['from_warehouse_id', 'to_warehouse_id', 'transfer_authorization'],
            'adjustment' => ['reason_detail', 'supervisor_approval'],
            'disposal' => ['disposal_reason', 'authorization_number', 'disposal_method'],
            default => null,
        };

        return $this->faker->optional(0.6)->passthrough($fields);
    }

    /**
     * Generate validation rules based on category.
     */
    private function generateValidationRules(string $category): ?array
    {
        $rules = match ($category) {
            'inbound' => [
                'quantity' => 'required|numeric|min:0.01',
                'unit_cost' => 'required|numeric|min:0',
                'supplier_id' => 'required|exists:suppliers,id',
            ],
            'outbound' => [
                'quantity' => 'required|numeric|min:0.01',
                'customer_id' => 'sometimes|exists:customers,id',
            ],
            'transfer' => [
                'from_warehouse_id' => 'required|exists:warehouses,id',
                'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            ],
            'adjustment' => [
                'quantity' => 'required|numeric',
                'reason_detail' => 'required|string|min:10',
            ],
            'disposal' => [
                'quantity' => 'required|numeric|min:0.01',
                'disposal_reason' => 'required|string',
            ],
            default => null,
        };

        return $this->faker->optional(0.5)->passthrough($rules);
    }

    /**
     * Create an inbound movement reason.
     */
    public function inbound(): static
    {
        return $this->state([
            'category' => 'inbound',
            'movement_type' => 'in',
        ]);
    }

    /**
     * Create an outbound movement reason.
     */
    public function outbound(): static
    {
        return $this->state([
            'category' => 'outbound',
            'movement_type' => 'out',
        ]);
    }

    /**
     * Create a transfer movement reason.
     */
    public function transfer(): static
    {
        return $this->state([
            'category' => 'transfer',
            'movement_type' => 'transfer',
        ]);
    }

    /**
     * Create an adjustment movement reason.
     */
    public function adjustment(): static
    {
        return $this->state([
            'category' => 'adjustment',
            'movement_type' => $this->faker->randomElement(['in', 'out']),
        ]);
    }

    /**
     * Create a disposal movement reason.
     */
    public function disposal(): static
    {
        return $this->state([
            'category' => 'disposal',
            'movement_type' => 'out',
        ]);
    }

    /**
     * Create a reason that requires approval.
     */
    public function requiresApproval(): static
    {
        return $this->state([
            'requires_approval' => true,
            'approval_threshold' => $this->faker->randomFloat(2, 500, 5000),
        ]);
    }

    /**
     * Create a reason that requires documentation.
     */
    public function requiresDocumentation(): static
    {
        return $this->state([
            'requires_documentation' => true,
            'required_fields' => ['document_number', 'authorization', 'notes'],
        ]);
    }

    /**
     * Create a reason with high approval threshold.
     */
    public function highValue(): static
    {
        return $this->state([
            'requires_approval' => true,
            'approval_threshold' => $this->faker->randomFloat(2, 5000, 50000),
            'requires_documentation' => true,
        ]);
    }

    /**
     * Create a simple reason (no approval required).
     */
    public function simple(): static
    {
        return $this->state([
            'requires_approval' => false,
            'requires_documentation' => false,
            'approval_threshold' => null,
            'required_fields' => null,
            'validation_rules' => null,
        ]);
    }

    /**
     * Create an active reason.
     */
    public function active(): static
    {
        return $this->state([
            'is_active' => true,
            'active_at' => now(),
        ]);
    }

    /**
     * Create an inactive reason.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
            'active_at' => null,
        ]);
    }

    /**
     * Create a reason with specific sort order.
     */
    public function withSortOrder(int $order): static
    {
        return $this->state([
            'sort_order' => $order,
        ]);
    }

    /**
     * Create a reason with specific code.
     */
    public function withCode(string $code): static
    {
        return $this->state([
            'code' => $code,
        ]);
    }
}
