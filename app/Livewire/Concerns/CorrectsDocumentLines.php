<?php

namespace App\Livewire\Concerns;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

/**
 * Shared state and rules of the screens that correct documents which already moved stock.
 */
trait CorrectsDocumentLines
{
    /** @var array<int, array{product_id: int|string, quantity: float|int|string, unit_price?: float|int|string, notes?: string|null}> */
    public array $lines = [];

    public string $reason = '';

    public function addLine(): void
    {
        $this->lines[] = ['product_id' => '', 'quantity' => '', 'unit_price' => '', 'notes' => ''];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * Only users with the correction permission of the same company (or a super admin) may correct.
     */
    protected function authorizeCorrection(int $companyId): void
    {
        $user = auth()->user();

        abort_unless($user->can('documents.correct'), 403, 'No tiene permiso para corregir documentos.');
        abort_unless($user->isSuperAdmin() || (int) $user->company_id === $companyId, 403, 'El documento pertenece a otra empresa.');
    }

    /**
     * Active products of the company plus any product already on the document.
     *
     * @param  array<int, int>  $keepIds
     * @return Collection<int, Product>
     */
    protected function productOptions(int $companyId, array $keepIds = []): Collection
    {
        return Product::query()
            ->where('company_id', $companyId)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $keepIds))
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
    }

    /**
     * @return Collection<int, Warehouse>
     */
    protected function warehouseOptions(int $companyId, array $keepIds = []): Collection
    {
        return Warehouse::query()
            ->where('company_id', $companyId)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $keepIds))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function correctionRules(bool $withPrice = false): array
    {
        $rules = [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999.99999'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($withPrice) {
            $rules['lines.*.unit_price'] = ['required', 'numeric', 'min:0', 'max:999999.99999'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function correctionMessages(): array
    {
        return [
            'reason.required' => 'Escriba el motivo de la corrección.',
            'reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'reason.max' => 'El motivo no puede exceder 1000 caracteres.',
            'lines.required' => 'Debe dejar al menos un producto.',
            'lines.min' => 'Debe dejar al menos un producto.',
            'lines.*.product_id.required' => 'Seleccione el producto.',
            'lines.*.product_id.integer' => 'Seleccione el producto.',
            'lines.*.quantity.required' => 'Escriba la cantidad.',
            'lines.*.quantity.numeric' => 'La cantidad debe ser un número.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor a cero.',
            'lines.*.quantity.max' => 'La cantidad no puede exceder 999,999.99999.',
            'lines.*.unit_price.required' => 'Escriba el costo unitario.',
            'lines.*.unit_price.numeric' => 'El costo unitario debe ser un número.',
            'lines.*.unit_price.min' => 'El costo unitario no puede ser negativo.',
            'lines.*.notes.max' => 'Las notas no pueden exceder 500 caracteres.',
        ];
    }

    /**
     * Drop the rows the user left empty before validating.
     */
    protected function withoutEmptyLines(): void
    {
        $this->lines = array_values(array_filter($this->lines, fn (array $line) => filled($line['product_id'] ?? null) || filled($line['quantity'] ?? null)));
    }
}
