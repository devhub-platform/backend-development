<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Musonza\Chat\Models\Conversation;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Illuminate\Support\Facades\URL;
use Throwable;

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
        $this->configureWritableCompiledViewsPath();

        Route::model('conversation', Conversation::class);

        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->numbers()
                ->symbols()
                ->max(16)
                ->mixedCase();
        });

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', MicrosoftProvider::class);
        });

//        if (config('app.env') === 'production') {
//            URL::forceScheme('https');
//        }
    }

    /**
     * Ensure Blade compiles templates into a writable directory.
     */
    private function configureWritableCompiledViewsPath(): void
    {
        $compiledPath = config('view.compiled');
        $compiledDirectory = is_string($compiledPath) ? dirname($compiledPath) : null;

        if ($this->isWritableDirectory($compiledDirectory)) {
            return;
        }

        $fallbackPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devhub' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views';

        try {
            File::ensureDirectoryExists($fallbackPath, 0755, true);
        } catch (Throwable) {
            return;
        }

        if (File::isWritable($fallbackPath)) {
            config(['view.compiled' => $fallbackPath]);
        }
    }

    private function isWritableDirectory(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        try {
            if (! File::isDirectory($path)) {
                File::ensureDirectoryExists($path, 0755, true);
            }
        } catch (Throwable) {
            return false;
        }

        return File::isWritable($path);
    }
}
