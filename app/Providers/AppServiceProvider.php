<?php

namespace App\Providers;

use App\Models\Post;
use App\Observers\PostObserver;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Musonza\Chat\Models\Conversation;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind concrete services to the container explicitly to avoid
        // autowiring issues on some deployment environments where
        // class files may be present but the automatic resolution fails.
        // These bindings are safe -- they only register if the class exists.
        if (class_exists(\App\Services\HackClubCdnService::class)) {
            $this->app->singleton(\App\Services\HackClubCdnService::class, function ($app) {
                return new \App\Services\HackClubCdnService();
            });
        }

        if (class_exists(\App\Services\AWSS3Service::class)) {
            $this->app->singleton(\App\Services\AWSS3Service::class, function ($app) {
                return new \App\Services\AWSS3Service();
            });
        }
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

        Gate::define('viewPulse', function (User $user) {
            return $user->role === 'admin';
        });

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', MicrosoftProvider::class);
        });

        Post::observe(PostObserver::class);

        // Configure Log Viewer authorization
        // This provides an additional layer of security on top of the viewLogViewer gate
        LogViewer::auth(function ($request) {
            // Allow any authenticated user to access Log Viewer
            // Adjust this logic if you need different access rules
            return $request->user() !== null;
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
