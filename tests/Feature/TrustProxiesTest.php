<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test/proxy-url', fn () => response()->json([
        'secure' => request()->secure(),
        'asset' => asset('build/app.css'),
        'ip' => request()->ip(),
    ]));
});

test('requests forwarded by the cloudflare tunnel generate https urls', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'bodega.ena.edu.sv',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-For' => '190.86.10.20',
        ])
        ->get('/_test/proxy-url')
        ->assertOk()
        ->assertJson([
            'secure' => true,
            'asset' => 'https://bodega.ena.edu.sv/build/app.css',
            'ip' => '190.86.10.20',
        ]);
});

test('requests without forwarded headers keep plain http', function () {
    $this->get('/_test/proxy-url')
        ->assertOk()
        ->assertJson(['secure' => false]);
});
