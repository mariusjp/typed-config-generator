<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Helper;

use CoderG33k\TypedConfigGenerator\Actions\GetClassForConfig;
use CoderG33k\TypedConfig;

final class DoesTypedConfigClassExist
{
    public static function determine(
        string $namespace,
        string $config,
    ): bool {
        $class = GetClassForConfig::execute(
            namespace: $namespace,
            config: $config,
        );

        if (!\class_exists($class)) {
            return false;
        }

        return \is_a($class, TypedConfig::class, true);
    }
}
