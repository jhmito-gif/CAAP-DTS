<?php

use App\Models\User;
use App\Notifications\CustomResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, CustomResetPassword::class);
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo(
        $user,
        CustomResetPassword::class,
        function (object $notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        }
    );
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo(
        $user,
        CustomResetPassword::class,
        function (object $notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

            $response->assertSessionHasNoErrors();

            return true;
        }
    );
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');