<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator;

use Illuminate\Support\Str;

abstract class TypedConfig
{
    public static function fromConfig(...$properties): static
    {
        // @todo Do this with a pipeline/mapper?
        $properties = \array_combine(
            \array_map(
                fn (string $key): string => Str::camel($key),
                \array_keys($properties),
            ),
            $properties,
        );

        return new static(...$properties);
    }
}
