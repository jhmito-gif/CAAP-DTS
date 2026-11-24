<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Let the sending office see an uploaded PDF before it is saved, so
        // they can mark where each signature goes while still in the send form.
        config(['livewire.temporary_file_upload.preview_mimes' => array_values(array_unique(array_merge(
            config('livewire.temporary_file_upload.preview_mimes', []),
            ['pdf']
        )))]);

        // @module('esign') ... @endmodule -- shows its contents only while
        // that module is switched on (App\Support\Modules).
        \Illuminate\Support\Facades\Blade::if('module', fn (string $module) => \App\Support\Modules::enabled($module));

        $this->auditSignIns();
    }

    /**
     * Signing in and out, and failed attempts, go to the audit trail. A
     * failed attempt keeps the email typed -- never the password.
     */
    private function auditSignIns(): void
    {
        $events = \Illuminate\Support\Facades\Event::getFacadeRoot();
        $audit = \App\Support\Audit::class;

        $events->listen(\Illuminate\Auth\Events\Login::class, fn ($event) => $audit::log(
            'auth.login', null, [], ['remember' => $event->remember ?: null], $event->user
        ));

        $events->listen(\Illuminate\Auth\Events\Logout::class, fn ($event) => $event->user
            ? $audit::log('auth.logout', null, [], [], $event->user)
            : null);

        // Fortify's own sign-in check does not say whose account it was, so
        // look it up by the email typed (never the password).
        $events->listen(\Illuminate\Auth\Events\Failed::class, function ($event) use ($audit) {
            $email = $event->credentials['email'] ?? null;
            $user = $event->user ?? (filled($email) ? \App\Models\User::where('email', $email)->first() : null);

            $audit::log('auth.failed', null, [], ['email' => $email], $user);
        });

        // An API token of a deactivated account stops working too.
        \Laravel\Sanctum\Sanctum::authenticateAccessTokensUsing(
            fn ($accessToken, bool $isValid) => $isValid && ! ($accessToken->tokenable?->isDeactivated() ?? false)
        );
    }
}
