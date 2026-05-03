<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Berkayk\OneSignal\OneSignalServiceProvider::class,
    AzureOss\Storage\BlobLaravel\AzureStorageBlobServiceProvider::class,
];
