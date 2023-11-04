<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Support;

use Coderg33k\TypedConfigGenerator\ConfigPipes\ConfigPipe;

final readonly class ResolvedDataPipeline
{
    /**
     * @param array<int, ConfigPipe> $pipes
     * @param array<string, mixed> $properties
     */
    public function __construct(
        public array $pipes,
        public string $class,
        public array $properties,
    ) {
    }

    public function execute(): array
    {
        $properties = $this->properties;

        foreach ($this->pipes as $pipe) {
            $piped = $pipe->handle($this->class, $properties);

            $properties = $piped;
        }

        return $properties;
    }
}
