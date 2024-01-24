<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Resolver;

use Coderg33k\TypedConfigGenerator\ConfigPipes\CastPropertiesConfigPipe;
use Coderg33k\TypedConfigGenerator\ConfigPipes\ScalarTypesPipe;
use Coderg33k\TypedConfigGenerator\Pipeline;
use Coderg33k\TypedConfigGenerator\ConfigPipes\CamelCaseConfigPipe;
use Coderg33k\TypedConfigGenerator\TypedConfig;

final class TypedClassFromConfigDataResolver
{
    /**
     * @param class-string<TypedConfig> $class
     */
    public function execute(
        string $class,
        mixed ...$properties,
    ): TypedConfig {
        $resolvedPipeline = Pipeline::create()
            ->send($class, $properties)
            // @todo: Send multiple pipes at once, so they can be configured in the config file.
            ->through(CamelCaseConfigPipe::class)
            ->through(CastPropertiesConfigPipe::class)
            ->through(ScalarTypesPipe::class)
            ->resolve();

        $pipedProperties = $resolvedPipeline->execute();

        return new $class(...$pipedProperties);
    }
}
