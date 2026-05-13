<?php

namespace App\Providers;

use App\Models\Post;
use App\Observers\PostObserver;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Musonza\Chat\Models\Conversation;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;
use Throwable;
use Laravel\Pulse\Facades\Pulse;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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

    public function boot(): void
    {
        Storage::extend('azure-storage-blob', function (Application $app, array $config): FilesystemAdapter {
            \AzureOss\Storage\BlobLaravel\AzureStorageBlobDiskConfig::validate($config);

            return new \AzureOss\Storage\BlobLaravel\AzureStorageBlobAdapter($config);
        });

        Pulse::user(fn($user) => [
            'name' => $user->name,
            'extra' => $user->email,
            'avatar' => $user->avatar_url,
        ]);

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

        LogViewer::auth(function ($request) {
            return $request->user() !== null;
        });
    }

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
        if (!$path) {
            return false;
        }

        try {
            if (!File::isDirectory($path)) {
                File::ensureDirectoryExists($path, 0755, true);
            }
        } catch (Throwable) {
            return false;
        }

        return File::isWritable($path);
    }
}