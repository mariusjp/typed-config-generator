<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Actions;

use Coderg33k\TypedConfigGenerator\Enums\Package;

final class GetConfigsForPredeterminedPackage
{
    /**
     * @return array<int, string>
     */
    public function execute(Package $package): array
    {
        return match ($package) {
            Package::Laravel => [
                'app',
                'auth',
                'broadcasting',
                'cache',
                'cors',
                'database',
                'filesystems',
                'hashing',
                'logging',
                'mail',
                'queue',
                'services',
                'session',
                'view',
            ],
            Package::Spatie => [
                'cors',
                'csp',
                'data',
                'flare',
                'ignition',
                'permission',
            ],
        };
    }
}
