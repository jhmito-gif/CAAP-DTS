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
    }
}
