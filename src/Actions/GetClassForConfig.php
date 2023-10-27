<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Actions;

use Coderg33k\TypedConfigGenerator\TypedConfig;
use Illuminate\Support\Str;

final class GetClassForConfig
{
    /**
     * @return class-string<TypedConfig>
     */
    public static function execute(
        string $namespace,
        string $config,
    ): string {
        return $namespace . 'Config\\' . \ucfirst(Str::camel($config));
    }
}
