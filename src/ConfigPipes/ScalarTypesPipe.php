<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\ConfigPipes;

final class ScalarTypesPipe implements ConfigPipe
{
    public function handle(string $class, array $properties): array
    {
        foreach ($properties as $name => $value) {
            if (!\is_scalar($value)) {
                continue;
            }

            if (\is_numeric($value)) {
                $properties[$name] = (int) $value;
            }
        }

        return $properties;
    }
}
