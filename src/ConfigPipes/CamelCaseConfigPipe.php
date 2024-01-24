<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\ConfigPipes;

use Illuminate\Support\Str;

final class CamelCaseConfigPipe implements ConfigPipe
{
    /**
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    public function handle(string $class, array $properties): array
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
