<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $donationNumber = 'DON-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6));
        $documentDate = fake()->dateTimeBetween('-60 days', '-1 day');
        $receptionDate = fake()->dateTimeBetween($documentDate, 'now');
        $donorType = fake()->randomElement(['individual', 'organization', 'government']);

        $donorNames = [
            'individual' => fake()->name(),
            'organization' => fake()->randomElement([
                'Fundación Ayuda Humanitaria',
                'Cruz Roja Salvadoreña',
                'Cáritas El Salvador',
                'ONG Esperanza Internacional',
                'Asociación de Beneficencia',
            ]),
            'government' => fake()->randomElement([
                'Ministerio de Salud',
                'Secretaría de Inclusión Social',
                'Alcaldía Municipal',
                'Gobierno Central',
            ]),
        ];

        $estimatedValue = fake()->randomFloat(2, 500, 50000);

        return [
            'company_id' => \App\Models\Company::factory(),
            'warehouse_id' => \App\Models\Warehouse::factory(),
            'donation_number' => $donationNumber,
            'slug' => \Illuminate\Support\Str::slug($donationNumber),
            'donor_name' => $donorNames[$donorType],
            'donor_type' => $donorType,
            'donor_contact' => fake()->name(),
            'donor_email' => fake()->optional()->safeEmail(),
            'donor_phone' => fake()->optional()->phoneNumber(),
            'donor_address' => fake()->optional()->address(),
            'document_type' => fake()->randomElement(['acta', 'carta', 'convenio', 'otro']),
            'document_number' => fake()->optional()->numerify('DOC-######'),
            'document_date' => $documentDate,
            'reception_date' => $receptionDate,
            'purpose' => fake()->optional()->randomElement([
                'Apoyo a comunidades vulnerables',
                'Emergencia por desastre natural',
                'Programa de alimentación',
                'Asistencia médica',
                'Educación y desarrollo',
            ]),
            'intended_use' => fake()->optional()->randomElement([
                'Distribución a familias',
                'Uso en proyecto específico',
                'Almacenamiento estratégico',
                'Apoyo institucional',
            ]),
            'project_name' => fake()->optional()->words(3, true),
            'estimated_value' => $estimatedValue,
            'tax_deduction_value' => fake()->optional()->randomFloat(2, $estimatedValue * 0.5, $estimatedValue),
            'status' => 'borrador',
            'approved_at' => null,
            'approved_by' => null,
            'received_at' => null,
            'received_by' => null,
            'notes' => fake()->optional()->sentence(),
            'admin_notes' => null,
            'conditions' => fake()->optional()->sentence(),
            'attachments' => [],
            'tax_receipt_required' => fake()->boolean(30),
            'tax_receipt_number' => null,
            'tax_receipt_date' => null,
            'is_active' => true,
            'active_at' => now(),
        ];
    }

    /**
     * Indicate that the donation is in draft status.
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
     * Indicate that the donation is pending approval.
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
     * Indicate that the donation is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprobado',
            'approved_at' => now()->subHours(2),
            'received_at' => null,
            'received_by' => null,
        ])->afterCreating(function ($donation) {
            if (! $donation->approved_by) {
                $donation->approved_by = \App\Models\User::factory()->create([
                    'company_id' => $donation->company_id,
                ])->id;
                $donation->save();
            }
        });
    }

    /**
     * Indicate that the donation is received.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'recibido',
            'approved_at' => now()->subDays(3),
            'received_at' => now()->subHours(1),
        ])->afterCreating(function ($donation) {
            if (! $donation->approved_by) {
                $donation->approved_by = \App\Models\User::factory()->create([
                    'company_id' => $donation->company_id,
                ])->id;
            }
            if (! $donation->received_by) {
                $donation->received_by = \App\Models\User::factory()->create([
                    'company_id' => $donation->company_id,
                ])->id;
            }
            $donation->save();
        });
    }

    /**
     * Indicate that the donation is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelado',
            'is_active' => false,
            'active_at' => null,
        ]);
    }

    /**
     * Indicate that the donation is from an individual.
     */
    public function fromIndividual(): static
    {
        return $this->state(fn (array $attributes) => [
            'donor_type' => 'individual',
            'donor_name' => fake()->name(),
        ]);
    }

    /**
     * Indicate that the donation is from an organization.
     */
    public function fromOrganization(): static
    {
        return $this->state(fn (array $attributes) => [
            'donor_type' => 'organization',
            'donor_name' => fake()->randomElement([
                'Fundación Ayuda Humanitaria',
                'Cruz Roja Salvadoreña',
                'Cáritas El Salvador',
            ]),
        ]);
    }

    /**
     * Indicate that the donation is from government.
     */
    public function fromGovernment(): static
    {
        return $this->state(fn (array $attributes) => [
            'donor_type' => 'government',
            'donor_name' => fake()->randomElement([
                'Ministerio de Salud',
                'Secretaría de Inclusión Social',
                'Alcaldía Municipal',
            ]),
        ]);
    }

    /**
     * Indicate that tax receipt is required.
     */
    public function withTaxReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_receipt_required' => true,
            'tax_receipt_number' => 'TR-'.fake()->numerify('######'),
            'tax_receipt_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the donation has details.
     */
    public function withDetails(int $count = 3): static
    {
        return $this->has(
            \App\Models\DonationDetail::factory()->count($count),
            'details'
        );
    }
}
