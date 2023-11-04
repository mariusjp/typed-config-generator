<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator;

use Coderg33k\TypedConfigGenerator\ConfigPipes\ConfigPipe;
use Coderg33k\TypedConfigGenerator\Support\ResolvedDataPipeline;

final class Pipeline
{
    protected string $class;
    protected mixed $passable;

    /** @var array<int, ConfigPipe> */
    protected array $pipes = [];

    protected string $method = 'handle';

    public static function create(): self
    {
        return new self();
    }

    public function send(
        string $class,
        mixed $passable
    ): self {
        $this->class = $class;
        $this->passable = $passable;

        return $this;
    }

    /**
     * @param class-string<ConfigPipe> $pipe
     */
    public function through(string $pipe): self
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
            properties: $this->passable,
        );
    }

    public function returnThen(): mixed
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    protected function then(\Closure $destination): mixed
    {
        $pipeline = \array_reduce(
            \array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination),
        );

        return $pipeline($this->passable);
    }

    protected function prepareDestination(\Closure $destination): \Closure
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (\Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    protected function carry(): \Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (\is_callable($pipe)) {
                        return $pipe($passable, $stack);
                    } else if (!\is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        $pipe = app($name);

                        $parameters = \array_merge([$passable, $stack], $parameters);
                    } else {
                        $parameters = [$passable, $stack];
                    }

                    return \method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}(...$parameters)
                        : $pipe(...$parameters);
                } catch (\Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * @return array<int, string>
     */
    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = \array_pad(\explode(':', $pipe, 2), 2, []);

        if (\is_string($parameters)) {
            $parameters = \explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * @throws \Throwable
     */
    protected function handleException($passable, \Throwable $e)
    {
        throw $e;
    }
}
