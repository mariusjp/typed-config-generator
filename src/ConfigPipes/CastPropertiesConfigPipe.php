<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\ConfigPipes;

use Coderg33k\TypedConfigGenerator\TypedConfig;

final class CastPropertiesConfigPipe implements ConfigPipe
{
    /**
     * @throws \ReflectionException
     */
    public function handle(string $class, array $properties): array
    {
        $reflection = new \ReflectionClass($class);
        $classProperties = $reflection->getProperties();

        foreach ($properties as $name => $value) {
            $reflectionProperty = \array_values(
                \array_filter(
                    $classProperties,
                    fn (\ReflectionProperty $property) => $property->getName() === $name,
                )
            );

            if (\count($reflectionProperty) === 0) {
                continue;
            }

            $reflectionProperty = $reflectionProperty[0];

            if (\is_a($reflectionProperty->getType()->getName(), TypedConfig::class, true)) {
                $cast = $reflectionProperty->getType()->getName()::fromConfig(...$value);

                $properties[$name] = $cast;
            }
        }

        return $properties;
    }
}
