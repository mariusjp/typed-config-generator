<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator;

use Coderg33k\TypedConfigGenerator\ConfigPipes\ConfigPipe;
use Coderg33k\TypedConfigGenerator\Resolver\ResolvedDataPipeline;

final class Pipeline
{
    private string $class;
    /** @var array<int|string, mixed> */
    private array $properties;

    /** @var array<int, class-string<ConfigPipe>|ConfigPipe> */
    private array $pipes = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param class-string<TypedConfig> $class
     * @param array<int|string, mixed> $properties
     */
    public function send(
        string $class,
        array $properties,
    ): self {
        $this->class = $class;
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param class-string<ConfigPipe>|ConfigPipe $pipe
     */
    public function through(string|ConfigPipe $pipe): self
    {
        $this->pipes[] = $pipe;

        return $this;
    }

    public function resolve(): ResolvedDataPipeline
    {
        $pipes = \array_map(
            fn (string|ConfigPipe $pipe) => \is_string($pipe) ? app($pipe) : $pipe,
            $this->pipes,
        );

        return new ResolvedDataPipeline(
            pipes: $pipes,
            class: $this->class,
            properties: $this->properties,
        );
    }
}
