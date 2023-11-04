<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Helper;

final readonly class IsArrayConfiguration
{
    public static function execute(mixed $data): bool
    {
        if (\is_array($data)) {
            foreach ($data as $key => $item) {
                if (!\is_string($key)) {
                    return true;
                }
            }
        }

        return false;
    }
}
