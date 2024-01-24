<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator;

use Coderg33k\TypedConfigGenerator\Actions\GetClassForConfig;
use Coderg33k\TypedConfigGenerator\Console\Commands\GenerateTypedConfig;
use Coderg33k\TypedConfigGenerator\Helper\DoesTypedConfigClassExist;
use Illuminate\Support\ServiceProvider;

final class TypedConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            GenerateTypedConfig::class,
        ]);

        $this->publishes([
            __DIR__ . '/../config/typed_config_generator.php' => config_path('typed_config_generator.php'),
        ]);
    }

    public function register(): void
    {
        // Only register the top-level config classes.
        foreach (\array_keys(config()->all()) as $config) {
            if (!\is_string($config)) {
                continue;
            }

            $doesConfigClassExist = DoesTypedConfigClassExist::determine(config: $config);

            if (!$doesConfigClassExist) {
                continue;
            }

            $class = GetClassForConfig::execute(config: $config);

            $this->app->singleton(
                $class,
                fn () => $class::fromConfig(...config($config)),
            );
        }
    }
}
