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
    }
}
