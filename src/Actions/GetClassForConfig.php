<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Actions;

use Illuminate\Support\Str;

final class GetClassForConfig
{
    public static function execute(string $config): string
    {
        return app()->getNamespace() . 'Config\\' . \ucfirst(Str::camel($config));
    }
}
