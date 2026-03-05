<?php

use App\Services\DteImportService;

beforeEach(function () {
    $this->service = new DteImportService;
});

it('parses valid DTE JSON correctly', function () {
    $json = json_encode([
        'identificacion' => ['codigoGeneracion' => 'TEST-UUID', 'tipoDte' => '03', 'fecEmi' => '2026-01-01', 'horEmi' => '10:00:00', 'numeroControl' => 'DTE-03-TEST'],
        'emisor' => ['nit' => '06141234567890', 'nombre' => 'Test Supplier', 'nrc' => '1234'],
        'receptor' => ['nit' => '05021234567890', 'nombre' => 'Test Receiver'],
        'cuerpoDocumento' => [
            ['numItem' => 1, 'descripcion' => 'Product A', 'cantidad' => 1, 'precioUni' => 10.0, 'ventaGravada' => 10.0],
        ],
        'resumen' => ['totalGravada' => 10.0, 'totalPagar' => 11.3],
    ]);

    $result = $this->service->parseJson($json);

    expect($result['success'])->toBeTrue()
        ->and($result['data']['cuerpoDocumento'])->toHaveCount(1);
});

it('fixes unescaped inch mark quotes in descriptions', function () {
    $json = '{"identificacion": {"codigoGeneracion": "UUID-1", "tipoDte": "03", "fecEmi": "2026-01-01", "horEmi": "10:00:00", "numeroControl": "DTE-03-TEST"}, "emisor": {"nit": "06141234567890", "nombre": "Test", "nrc": "1234"}, "receptor": {"nit": "05021234567890"}, "cuerpoDocumento": [{"numItem": 1, "descripcion": "ESPATULA 14" SILICON", "cantidad": 1, "precioUni": 10.0, "ventaGravada": 10.0}], "resumen": {"totalGravada": 10.0, "totalPagar": 11.3}}';

    $result = $this->service->parseJson($json);

    expect($result['success'])->toBeTrue()
        ->and($result['data']['cuerpoDocumento'][0]['descripcion'])->toBe('ESPATULA 14" SILICON');
});

it('fixes premature closing brace before firma and sello', function () {
    $json = '{"identificacion": {"codigoGeneracion": "UUID-2", "tipoDte": "03", "fecEmi": "2026-01-01", "horEmi": "10:00:00", "numeroControl": "DTE-03-TEST"}, "emisor": {"nit": "06141234567890", "nombre": "Test", "nrc": "1234"}, "receptor": {"nit": "05021234567890"}, "cuerpoDocumento": [{"numItem": 1, "descripcion": "Product", "cantidad": 1, "precioUni": 10.0, "ventaGravada": 10.0}], "resumen": {"totalGravada": 10.0, "totalPagar": 11.3}}, "firma": "abc123", "sello": "def456"';

    $result = $this->service->parseJson($json);

    expect($result['success'])->toBeTrue()
        ->and($result['data']['firma'])->toBe('abc123')
        ->and($result['data']['sello'])->toBe('def456');
});

it('fixes both unescaped quotes and premature brace simultaneously', function () {
    $json = '{"identificacion": {"codigoGeneracion": "UUID-3", "tipoDte": "03", "fecEmi": "2026-01-01", "horEmi": "10:00:00", "numeroControl": "DTE-03-TEST"}, "emisor": {"nit": "06141234567890", "nombre": "Test", "nrc": "1234"}, "receptor": {"nit": "05021234567890"}, "cuerpoDocumento": [{"numItem": 1, "descripcion": "MOLDE 9.5X5.5\" TRICOLOR", "cantidad": 1, "precioUni": 10.0, "ventaGravada": 10.0}], "resumen": {"totalGravada": 10.0, "totalPagar": 11.3}}, "firma": "jwt-token", "sello": "hash-value"';

    $result = $this->service->parseJson($json);

    expect($result['success'])->toBeTrue()
        ->and($result['data']['firma'])->toBe('jwt-token')
        ->and($result['data']['cuerpoDocumento'][0]['descripcion'])->toContain('9.5X5.5"');
});

it('returns error for completely invalid JSON', function () {
    $result = $this->service->parseJson('not json at all');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Error al parsear JSON');
});

it('returns error when required sections are missing', function () {
    $json = json_encode(['identificacion' => ['codigoGeneracion' => 'UUID']]);

    $result = $this->service->parseJson($json);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('no encontrada');
});
