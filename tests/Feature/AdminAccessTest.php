<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('does not expose a separate admin login page', function () {
    expect(Route::has('filament.admin.auth.login'))->toBeFalse();

    $this->get('/admin/login')->assertNotFound();
});

it('sends guests from the admin panel to the application login page', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('forbids ordinary users from the admin panel', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
        ->get('/admin')
        ->assertForbidden();
});

it('allows administrators into the admin panel', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
        ->get('/admin')
        ->assertOk();
});
