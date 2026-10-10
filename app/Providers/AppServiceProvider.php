<?php

namespace App\Providers;

use Anthropic\Client;
use App\Contracts\MenuAnalyst;
use App\Services\Claude\ClaudeMenuAnalyst;
use App\Services\Claude\MockMenuAnalyst;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function (): Client {
            $apiKey = config('services.anthropic.key');

            // Sans clé explicite, le SDK résout lui-même les identifiants
            // (ANTHROPIC_API_KEY, ANTHROPIC_AUTH_TOKEN ou profil `ant auth login`).
            return new Client(apiKey: $apiKey ?: null);
        });

        $this->app->singleton(ImageManager::class, fn (): ImageManager => new ImageManager(new GdDriver));

        $this->app->bind(MenuAnalyst::class, function (Application $app): MenuAnalyst {
            if (config('opticarte.mock')) {
                return $app->make(MockMenuAnalyst::class);
            }

            return new ClaudeMenuAnalyst($app->make(Client::class), (string) config('opticarte.model'));
        });
    }

    public function boot(): void
    {
        //
    }
}
