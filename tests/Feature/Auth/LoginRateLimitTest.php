<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;

beforeEach(function () {
    RateLimiter::clear('login-ip|127.0.0.1');
    RateLimiter::clear('password-reset|127.0.0.1');
});

function failLogin(string $email, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        Volt::test('auth.login')
            ->set('email', $email)
            ->set('password', 'wrong-password')
            ->call('login');
    }
}

test('an account is locked for five minutes after five failed attempts from the same ip', function () {
    $user = User::factory()->create();

    failLogin($user->email, 5);

    Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();

    $seconds = RateLimiter::availableIn(strtolower($user->email).'|127.0.0.1');

    expect($seconds)->toBeGreaterThan(240)->toBeLessThanOrEqual(300);
});

test('one ip trying many different emails is blocked even with a valid password', function () {
    $user = User::factory()->create();

    foreach (range(1, 20) as $i) {
        failLogin("intruso{$i}@example.com", 1);
    }

    Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('a successful login does not reset the per ip counter', function () {
    $user = User::factory()->create();

    failLogin('alguien@example.com', 3);

    Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    expect(RateLimiter::attempts('login-ip|127.0.0.1'))->toBe(3);
});

test('a few failed attempts do not block a legitimate login', function () {
    $user = User::factory()->create();

    failLogin($user->email, 4);

    Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('password reset requests are limited per ip', function () {
    foreach (range(1, 5) as $i) {
        Volt::test('auth.forgot-password')
            ->set('email', "usuario{$i}@example.com")
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors();
    }

    Volt::test('auth.forgot-password')
        ->set('email', 'usuario6@example.com')
        ->call('sendPasswordResetLink')
        ->assertHasErrors('email');
});
