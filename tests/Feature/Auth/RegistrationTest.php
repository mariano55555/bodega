<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('public registration is disabled by default', function () {
    $this->get('/register')->assertNotFound();

    Volt::test('auth.register')->assertNotFound();
});

test('registration screen can be rendered when registration is enabled', function () {
    config(['auth.registration_enabled' => true]);

    Volt::test('auth.register')->assertOk();
});

test('new users can register when registration is enabled', function () {
    config(['auth.registration_enabled' => true]);

    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
