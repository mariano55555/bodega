<?php

namespace App\Services;

use App\Models\DteImport;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DteImportService
{
    /**
     * Parse and validate a DTE JSON file.
     *
     * @param  UploadedFile|string  $input  File or JSON string
     * @return array{success: bool, data?: array, error?: string}
     */
    public function parseJson(UploadedFile|string $input): array
    {
        try {
            if ($input instanceof UploadedFile) {
                $content = file_get_contents($input->getRealPath());
            } else {
                $content = $input;
            }

            // Remove BOM if present
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Error al parsear JSON: '.json_last_error_msg(),
                ];
            }

            // Validate required structure
            $validation = $this->validateDteStructure($data);
            if (! $validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                ];
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Error parsing DTE JSON', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Error procesando el archivo: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Validate the DTE JSON structure.
     *
     * @param  array  $data  Parsed JSON data
     * @return array{valid: bool, error?: string}
     */
    public function validateDteStructure(array $data): array
    {
        $requiredSections = ['identificacion', 'emisor', 'cuerpoDocumento', 'resumen'];

        foreach ($requiredSections as $section) {
            if (! isset($data[$section])) {
                return [
                    'valid' => false,
                    'error' => "Sección requerida '{$section}' no encontrada en el DTE",
                ];
            }
        }

        // Validate identificacion
        if (empty($data['identificacion']['codigoGeneracion'])) {
            return [
                'valid' => false,
                'error' => 'Código de generación no encontrado',
            ];
        }

        // Validate emisor
        if (empty($data['emisor']['nit']) || empty($data['emisor']['nombre'])) {
            return [
                'valid' => false,
                'error' => 'Datos del emisor incompletos (NIT y nombre requeridos)',
            ];
        }

        // Validate cuerpoDocumento
        if (! is_array($data['cuerpoDocumento']) || empty($data['cuerpoDocumento'])) {
            return [
                'valid' => false,
                'error' => 'El documento no contiene items',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Create a DTE import record from parsed JSON.
     *
     * @param  array  $data  Parsed and validated JSON data
     * @param  int  $companyId  Company ID
     */
    public function createImport(array $data, int $companyId): DteImport
    {
        $identificacion = $data['identificacion'];
        $emisor = $data['emisor'];
        $resumen = $data['resumen'];

        return DteImport::create([
            'company_id' => $companyId,
            'codigo_generacion' => $identificacion['codigoGeneracion'],
            'numero_control' => $identificacion['numeroControl'] ?? null,
            'tipo_dte' => $identificacion['tipoDte'] ?? '01',
            'fecha_emision' => $identificacion['fecEmi'],
            'hora_emision' => $identificacion['horEmi'] ?? null,
            'emisor_nit' => $emisor['nit'],
            'emisor_nrc' => $emisor['nrc'] ?? null,
            'emisor_nombre' => $emisor['nombre'],
            'total_gravado' => $resumen['totalGravada'] ?? 0,
            'total_iva' => $resumen['totalIva'] ?? 0,
            'total_pagar' => $resumen['totalPagar'] ?? 0,
            'json_original' => $data,
            'status' => 'pending',
            'is_active' => true,
        ]);
    }

    /**
     * Find or create supplier from DTE emisor data.
     *
     * @param  array  $emisor  Emisor data from DTE
     * @param  int  $companyId  Company ID
     * @return array{supplier: Supplier, created: bool}
     */
    public function findOrCreateSupplier(array $emisor, int $companyId): array
    {
        // Try to find by tax_id (NIT)
        $supplier = Supplier::forCompany($companyId)
            ->where('tax_id', $emisor['nit'])
            ->first();

        if ($supplier) {
            return ['supplier' => $supplier, 'created' => false];
        }

        // Create new supplier
        $direccion = $emisor['direccion'] ?? [];

        $supplier = Supplier::create([
            'company_id' => $companyId,
            'name' => $emisor['nombreComercial'] ?? $emisor['nombre'],
            'legal_name' => $emisor['nombre'],
            'tax_id' => $emisor['nit'],
            'email' => $emisor['correo'] ?? null,
            'phone' => $emisor['telefono'] ?? null,
            'address' => $direccion['complemento'] ?? null,
            'is_active' => true,
        ]);

        return ['supplier' => $supplier, 'created' => true];
    }

    /**
     * Analyze DTE items and find matching products.
     *
     * @param  DteImport  $dteImport  The DTE import record
     * @param  int  $supplierId  Supplier ID
     * @return array Array of item analysis results
     */
    public function analyzeItems(DteImport $dteImport, int $supplierId): array
    {
        $items = $dteImport->items;
        $companyId = $dteImport->company_id;
        $results = [];

        foreach ($items as $item) {
            // Extract code and name from description (e.g., "174601 Fresa")
            $parsed = $this->parseItemDescription($item['descripcion'] ?? '');

            $result = [
                'num_item' => $item['numItem'],
                'supplier_code' => $parsed['code'],
                'supplier_description' => $item['descripcion'],
                'parsed_name' => $parsed['name'],
                'quantity' => $item['cantidad'],
                'unit_price' => $item['precioUni'],
                'total' => $item['ventaGravada'],
                'unit_measure_code' => $item['uniMedida'],
                'iva' => $item['ivaItem'] ?? 0,
                'product_id' => null,
                'product' => null,
                'match_type' => 'none',
                'needs_creation' => true,
            ];

            // Try to find product by supplier code
            $productSupplier = ProductSupplier::forCompany($companyId)
                ->forSupplier($supplierId)
                ->bySupplierCode($parsed['code'])
                ->with('product')
                ->first();

            if ($productSupplier) {
                $result['product_id'] = $productSupplier->product_id;
                $result['product'] = $productSupplier->product;
                $result['match_type'] = 'supplier_code';
                $result['needs_creation'] = false;
            } else {
                // Try to find by name similarity
                $product = Product::query()
                    ->where('company_id', $companyId)
                    ->where(function ($query) use ($parsed) {
                        $query->where('name', 'like', '%'.$parsed['name'].'%')
                            ->orWhere('sku', $parsed['code']);
                    })
                    ->first();

                if ($product) {
                    $result['product_id'] = $product->id;
                    $result['product'] = $product;
                    $result['match_type'] = 'name_similarity';
                    $result['needs_creation'] = false;
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Parse item description to extract code and name.
     *
     * @param  string  $description  Item description (e.g., "174601 Fresa")
     * @return array{code: string, name: string}
     */
    public function parseItemDescription(string $description): array
    {
        // Pattern: numeric code followed by product name
        if (preg_match('/^(\d+)\s+(.+)$/', trim($description), $matches)) {
            return [
                'code' => $matches[1],
                'name' => trim($matches[2]),
            ];
        }

        // If no code found, use full description as name
        return [
            'code' => '',
            'name' => trim($description),
        ];
    }

    /**
     * Create a product from DTE item data.
     *
     * @param  array  $itemData  Analyzed item data
     * @param  int  $companyId  Company ID
     * @param  int  $supplierId  Supplier ID
     * @param  array  $additionalData  Additional product data (category_id, unit_of_measure_id, etc.)
     */
    public function createProductFromItem(
        array $itemData,
        int $companyId,
        int $supplierId,
        array $additionalData = []
    ): Product {
        return DB::transaction(function () use ($itemData, $companyId, $supplierId, $additionalData) {
            // Generate unique SKU
            $sku = $additionalData['sku'] ?? $this->generateSku($itemData['supplier_code'], $companyId);

            $product = Product::create([
                'company_id' => $companyId,
                'name' => $additionalData['name'] ?? $itemData['parsed_name'],
                'sku' => $sku,
                'description' => $additionalData['description'] ?? null,
                'category_id' => $additionalData['category_id'] ?? null,
                'unit_of_measure_id' => $additionalData['unit_of_measure_id'] ?? null,
                'unit_of_measure' => $additionalData['unit_of_measure'] ?? 'unidad',
                'cost' => $itemData['unit_price'],
                'price' => $additionalData['price'] ?? null,
                'primary_supplier_id' => $supplierId,
                'track_inventory' => true,
                'is_active' => true,
            ]);

            // Create product-supplier mapping
            ProductSupplier::create([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'supplier_id' => $supplierId,
                'supplier_code' => $itemData['supplier_code'],
                'supplier_description' => $itemData['supplier_description'],
                'supplier_cost' => $itemData['unit_price'],
                'supplier_unit_measure_code' => $itemData['unit_measure_code'],
                'is_preferred' => true,
                'is_active' => true,
            ]);

            return $product;
        });
    }

    /**
     * Link existing product to supplier.
     *
     * @param  int  $productId  Product ID
     * @param  int  $supplierId  Supplier ID
     * @param  array  $itemData  DTE item data
     * @param  int  $companyId  Company ID
     */
    public function linkProductToSupplier(
        int $productId,
        int $supplierId,
        array $itemData,
        int $companyId
    ): ProductSupplier {
        return ProductSupplier::updateOrCreate(
            [
                'company_id' => $companyId,
                'supplier_id' => $supplierId,
                'supplier_code' => $itemData['supplier_code'],
            ],
            [
                'product_id' => $productId,
                'supplier_description' => $itemData['supplier_description'],
                'supplier_cost' => $itemData['unit_price'],
                'supplier_unit_measure_code' => $itemData['unit_measure_code'],
                'last_purchase_at' => now(),
                'last_purchase_price' => $itemData['unit_price'],
                'is_active' => true,
            ]
        );
    }

    /**
     * Generate unique SKU for a product.
     *
     * @param  string  $supplierCode  Supplier's product code
     * @param  int  $companyId  Company ID
     */
    private function generateSku(string $supplierCode, int $companyId): string
    {
        $baseSku = 'PROD-'.$supplierCode;

        // Check if exists
        $exists = Product::where('company_id', $companyId)
            ->where('sku', $baseSku)
            ->exists();

        if (! $exists) {
            return $baseSku;
        }

        // Add suffix
        $counter = 1;
        do {
            $sku = $baseSku.'-'.$counter;
            $exists = Product::where('company_id', $companyId)
                ->where('sku', $sku)
                ->exists();
            $counter++;
        } while ($exists);

        return $sku;
    }

    /**
     * Save mapping data to DTE import.
     *
     * @param  DteImport  $dteImport  The DTE import
     * @param  array  $mappingData  Mapping configuration
     */
    public function saveMappingData(DteImport $dteImport, array $mappingData): void
    {
        $dteImport->update([
            'mapping_data' => $mappingData,
            'status' => 'reviewing',
        ]);
    }

    /**
     * Check if DTE with same codigo_generacion already exists (not soft-deleted).
     *
     * @param  string  $codigoGeneracion  DTE codigo generacion
     * @param  int  $companyId  Company ID
     */
    public function dteExists(string $codigoGeneracion, int $companyId): bool
    {
        return DteImport::forCompany($companyId)
            ->where('codigo_generacion', $codigoGeneracion)
            ->exists();
    }

    /**
     * Get summary of DTE for display.
     *
     * @param  array  $data  Parsed DTE data
     * @return array Summary data
     */
    public function getDteSummary(array $data): array
    {
        $identificacion = $data['identificacion'];
        $emisor = $data['emisor'];
        $resumen = $data['resumen'];
        $items = $data['cuerpoDocumento'] ?? [];

        return [
            'codigo_generacion' => $identificacion['codigoGeneracion'],
            'numero_control' => $identificacion['numeroControl'] ?? null,
            'tipo_dte' => $this->getTipoDteLabel($identificacion['tipoDte'] ?? '01'),
            'fecha_emision' => $identificacion['fecEmi'],
            'hora_emision' => $identificacion['horEmi'] ?? null,
            'emisor' => [
                'nit' => $emisor['nit'],
                'nombre' => $emisor['nombre'],
                'nombre_comercial' => $emisor['nombreComercial'] ?? $emisor['nombre'],
            ],
            'totales' => [
                'total_gravado' => $resumen['totalGravada'] ?? 0,
                'total_iva' => $resumen['totalIva'] ?? 0,
                'total_pagar' => $resumen['totalPagar'] ?? 0,
            ],
            'num_items' => count($items),
        ];
    }

    /**
     * Get tipo DTE label.
     */
    private function getTipoDteLabel(string $tipo): string
    {
        return match ($tipo) {
            '01' => 'Factura',
            '03' => 'Comprobante de Crédito Fiscal',
            '04' => 'Nota de Remisión',
            '05' => 'Nota de Crédito',
            '06' => 'Nota de Débito',
            '07' => 'Comprobante de Retención',
            '08' => 'Comprobante de Liquidación',
            '09' => 'Documento Contable de Liquidación',
            '11' => 'Factura de Exportación',
            '14' => 'Factura de Sujeto Excluido',
            '15' => 'Comprobante de Donación',
            default => 'Documento Tributario',
        };
    }
}
