<?php

namespace App\Providers;

use App\Contracts\FrenchGeography;
use App\Contracts\MenuAnalyst;
use App\Contracts\PhotoClassifier;
use App\Contracts\PlacesClient;
use App\Contracts\WebsiteCapture;
use App\Services\Ai\AiMenuAnalyst;
use App\Services\Ai\AiPhotoClassifier;
use App\Services\Ai\MockMenuAnalyst;
use App\Services\Ai\MockPhotoClassifier;
use App\Services\Browser\BrowsershotWebsiteCapture;
use App\Services\Browser\FakeWebsiteCapture;
use App\Services\Geo\DemoGeography;
use App\Services\Geo\GeoApiGeography;
use App\Services\Places\DemoPlacesClient;
use App\Services\Places\GooglePlacesClient;
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

        $this->app->bind(FrenchGeography::class, function (Application $app): FrenchGeography {
            return config('opticarte.mock')
                ? $app->make(DemoGeography::class)
                : $app->make(GeoApiGeography::class);
        });

        $this->app->bind(PlacesClient::class, function (Application $app): PlacesClient {
            return config('opticarte.mock')
                ? $app->make(DemoPlacesClient::class)
                : $app->make(GooglePlacesClient::class);
        });

        $this->app->bind(PhotoClassifier::class, function (Application $app): PhotoClassifier {
            return config('opticarte.mock')
                ? $app->make(MockPhotoClassifier::class)
                : $app->make(AiPhotoClassifier::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
