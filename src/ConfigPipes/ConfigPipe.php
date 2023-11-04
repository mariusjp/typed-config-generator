<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\ConfigPipes;

interface ConfigPipe
{
    // This is the $next($passable); version
//    public function handle(array $properties, \Closure $next): mixed;

    public function handle(string $class, array $properties): array;
}
