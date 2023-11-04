<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\ConfigPipes;

use Illuminate\Support\Str;

final class CamelCaseConfigPipe implements ConfigPipe
{
    public function handle(string $class, mixed $properties): array
    {
        return \array_combine(
            \array_map(
                fn (string $key): string => Str::camel($key),
                \array_keys($properties),
            ),
            $properties,
        );
    }
}
