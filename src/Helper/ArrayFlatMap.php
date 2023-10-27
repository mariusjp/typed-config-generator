<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Helper;

final class ArrayFlatMap
{
    public function execute(
        array $array,
        string $prefix = '',
    ): array {
        $result = [];

        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $result = \array_merge($result, $this->execute($value, $key));
            } else {
                $result[] = [$prefix, $value];
            }
        }

        return $result;
    }
}
