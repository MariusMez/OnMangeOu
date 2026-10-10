<?php

namespace App\Providers;

use App\Contracts\MenuAnalyst;
use App\Contracts\WebsiteCapture;
use App\Services\Ai\AiMenuAnalyst;
use App\Services\Ai\MockMenuAnalyst;
use App\Services\Browser\BrowsershotWebsiteCapture;
use App\Services\Browser\FakeWebsiteCapture;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn (): ImageManager => new ImageManager(new GdDriver));

        $this->app->bind(MenuAnalyst::class, function (Application $app): MenuAnalyst {
            return config('opticarte.mock')
                ? $app->make(MockMenuAnalyst::class)
                : $app->make(AiMenuAnalyst::class);
        });

        $this->app->bind(WebsiteCapture::class, function (Application $app): WebsiteCapture {
            return config('opticarte.mock')
                ? $app->make(FakeWebsiteCapture::class)
                : $app->make(BrowsershotWebsiteCapture::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
