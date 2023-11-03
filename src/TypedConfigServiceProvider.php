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
    }

    public function register(): void
    {
        foreach (\array_keys(config()->all()) as $config) {
            $doesConfigClassExist = DoesTypedConfigClassExist::determine(
                namespace: $this->app->getNamespace(),
                config: $config,
            );

            if (!$doesConfigClassExist) {
                continue;
            }

            $class = GetClassForConfig::execute(
                namespace: $this->app->getNamespace(),
                config: $config,
            );

//            $this->app->singleton(
//                $class,
//                fn () => $class::fromConfig(...config($config)),
//            );
        }
    }
}
