<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    Grohiro\LaravelCamelCaseJson\CamelCaseJsonResponseServiceProvider::class,
    Intervention\Image\Laravel\ServiceProvider::class,
    LaravelFCM\FCMServiceProvider::class,
    PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
];
