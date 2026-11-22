<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Notification::fake();
});

it('shows the forgot password page and links to it from login', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/ForgotPassword'));

    $this->get('/login')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('copy.forgot_link', __('password_reset.forgot_link')));
});

it('emails a reset link to an active user', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('status', __('password_reset.sent'));

    Notification::assertSentTo($user, ResetPassword::class);
});

it('gives the same answer and sends nothing for unknown, invited or inactive emails', function (string $email, ?string $state) {
    if ($state !== null) {
        User::factory()->{$state}()->create(['email' => $email]);
    }

    $this->post('/forgot-password', ['email' => $email])
        ->assertSessionHas('status', __('password_reset.sent'));

    Notification::assertNothingSent();
})->with([
    'unknown' => ['nobody@example.com', null],
    'invited' => ['invited@example.com', 'invited'],
    'inactive' => ['inactive@example.com', 'inactive'],
]);

it('resets the password with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Brand-new-123',
        'password_confirmation' => 'Brand-new-123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('Brand-new-123', $user->fresh()->password))->toBeTrue();
});

it('refuses a wrong token', function () {
    $user = User::factory()->create();

    $this->post('/reset-password', [
        'token' => 'wrong',
        'email' => $user->email,
        'password' => 'Brand-new-123',
        'password_confirmation' => 'Brand-new-123',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('Brand-new-123', $user->fresh()->password))->toBeFalse();
});

it('does not let a deactivated user reset with a token issued before', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $user->deactivate();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Brand-new-123',
        'password_confirmation' => 'Brand-new-123',
    ])->assertSessionHasErrors('email');
});

it('offers password reset on the admin panel login', function () {
    $this->get('/admin/password-reset/request')->assertOk();
});
