<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Musonza\Chat\Models\Conversation;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Illuminate\Support\Facades\URL;

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

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
