<?php

namespace App\Rules;

use App\Models\Company;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentDateNotTooOld implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(private ?int $companyId = null) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Reject document dates older than the company-configured minimum.
     * Skips silently when the value is empty, the company cannot be resolved,
     * or the company has no restriction configured.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $company = $this->resolveCompany();

        if (! $company) {
            return;
        }

        $minDate = $company->minDocumentDate();

        if ($minDate === null) {
            return;
        }

        try {
            $date = CarbonImmutable::parse($value)->startOfDay();
        } catch (\Exception) {
            return;
        }

        if ($date->lt($minDate)) {
            $fail("La fecha del documento no puede ser anterior al {$minDate->format('d/m/Y')} según la configuración de la empresa.");
        }
    }

    /**
     * Resolve the company whose configuration applies to this document.
     * Priority: explicit id > company_id in payload > the selected warehouse's
     * company > the authenticated user's company. This keeps the rule working
     * for super admins (who have no company_id) by using the document's target.
     */
    private function resolveCompany(): ?Company
    {
        $companyId = $this->companyId
            ?? ($this->data['company_id'] ?? null)
            ?? $this->companyIdFromWarehouse()
            ?? auth()->user()?->company_id;

        if (! $companyId) {
            return null;
        }

        return Company::find($companyId);
    }

    private function companyIdFromWarehouse(): ?int
    {
        $warehouseId = $this->data['warehouse_id']
            ?? $this->data['from_warehouse_id']
            ?? null;

        if (! $warehouseId) {
            return null;
        }

        return Warehouse::whereKey($warehouseId)->value('company_id');
    }
}
