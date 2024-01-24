<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\ConfigPipes;

interface ConfigPipe
{
    /**
     * @param array<string, mixed> $properties
     * @return array<int|string, mixed>
     */
    public function handle(string $class, array $properties): array;
}
