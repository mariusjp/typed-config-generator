<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\ConfigPipes;

use Coderg33k\TypedConfigGenerator\TypedConfig;

final class CastPropertiesConfigPipe implements ConfigPipe
{
    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    public function handle(string $class, array $properties): array
    {
        if (!\class_exists($class)) {
            return $properties;
        }

        $reflection = new \ReflectionClass($class);
        $classProperties = $reflection->getProperties();

        foreach ($properties as $name => $value) {
            if (!\is_iterable($value)) {
                continue;
            }

            $reflectionProperty = \array_values(
                \array_filter(
                    $classProperties,
                    fn (\ReflectionProperty $property) => $property->getName() === $name,
                ),
            );

            if (\count($reflectionProperty) === 0) {
                continue;
            }

            /** @var \ReflectionProperty $reflectionProperty */
            $reflectionProperty = $reflectionProperty[0];

            $reflectionPropertyType = $reflectionProperty->getType();
            if (!$reflectionPropertyType instanceof \ReflectionNamedType) {
                continue;
            }

            if (\is_a($reflectionPropertyType->getName(), TypedConfig::class, true)) {
                $cast = $reflectionPropertyType->getName()::fromConfig(...$value);

                $properties[$name] = $cast;
            }
        }

        return $properties;
    }
}
