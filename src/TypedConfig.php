<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator;

use Coderg33k\TypedConfigGenerator\Resolver\TypedClassFromConfigDataResolver;

abstract readonly class TypedConfig
{
    /**
     * @param string|array<string, mixed>|bool $properties
     */
    final public static function fromConfig(mixed ...$properties): static
    {
        return app(TypedClassFromConfigDataResolver::class)->execute(
            static::class,
            ...$properties,
        );
    }
}
