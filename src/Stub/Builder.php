<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Stub;

use Coderg33k\TypedConfigGenerator\Data\ConfigTree;
use Coderg33k\TypedConfigGenerator\Enums\PropertyType;
use Coderg33k\TypedConfigGenerator\Helper\IsArrayConfiguration;
use Coderg33k\TypedConfigGenerator\Helper\ReservedName;
use Coderg33k\TypedConfigGenerator\TypedConfig;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Safe\Exceptions\FilesystemException;

final readonly class Builder
{
    public function __construct(
        private Filesystem $files,
    ) {
    }

    public function buildTree(
        string $config,
        ConfigTree &$tree,
    ): void {
        /** @var array<string, mixed> $configData */
        $configData = config($config);

        foreach ($configData as $key => $value) {
            $isNull = false;

            if (\is_array($value)) {
                if (IsArrayConfiguration::execute($value)) {
                    $tree->createAndPushBranch(
                        config: $key,
                        value: $value,
                        type: PropertyType::Array,
                    );

                    continue;
                }

                if ($this->skipDeepDive($config, $key)) {
                    $tree->createAndPushBranch(
                        config: $key,
                        value: $value,
                        type: PropertyType::Array,
                    );
                } else {
                    $deepConfig = \sprintf('%s.%s', $config, $key);
                    $deepTree = ConfigTree::create($key, config($deepConfig));
                    $this->buildTree(
                        config: $deepConfig,
                        tree: $deepTree,
                    );
                    $tree->addBranch($deepTree);
                }

                continue;
            } else if (\is_bool($value)) {
                $type = PropertyType::Boolean;
            } else if (\is_float($value)) {
                $type = PropertyType::Float;
            } else if (\is_int($value)) {
                $type = PropertyType::Integer;
                // phpcs:disable Generic.PHP.ForbiddenFunctions.Found
            } else if (\is_null($value)) {
                // phpcs:enable
                $type = PropertyType::Unknown;
                $isNull = true;
            } else if (\is_string($value)) {
                $type = PropertyType::String;
            } else {
                $type = PropertyType::Unknown;
            }

            $tree->createAndPushBranch(
                config: $key,
                value: $value,
                type: $type,
                isNull: $isNull,
            );
        }
    }

    /**
     * @param array<string, array<int, string>> $nullValues
     * @throws FilesystemException
     */
    public function handle(
        string $config,
        string $namespace,
        array &$nullValues,
        Config $stubConfiguration,
    ): void {
        $this->determineProperties(
            config: $config,
            namespace: $namespace,
            nullValues: $nullValues,
            stubConfiguration: $stubConfiguration,
        );
    }

    /**
     * @param array<string, array<int, string>> $nullValues
     * @throws FilesystemException
     */
    private function determineProperties(
        string $config,
        string $namespace,
        array &$nullValues,
        Config $stubConfiguration,
    ): string {
        $properties = [];
        /** @var array<string, mixed> $configData */
        $configData = config($config);

        foreach ($configData as $key => $value) {
            if (\is_array($value)) {
                if ($stubConfiguration->useFlat) {
                    $properties[$key] = 'array';
                    continue;
                }

                if (IsArrayConfiguration::execute($value)) {
                    $properties[$key] = 'array';
                    continue;
                }

                if ($this->skipDeepDive($config, $key)) {
                    $properties[$key] = 'array';
                } else {
                    $properties[$key] = $this->determineProperties(
                        config: \sprintf('%s.%s', $config, $key),
                        namespace: $namespace,
                        nullValues: $nullValues,
                        stubConfiguration: $stubConfiguration,
                    );
                }
            } else if (\is_bool($value)) {
                $properties[$key] = 'bool';
            } else if (\is_float($value)) {
                $properties[$key] = 'float';
            } else if (\is_int($value) || \is_numeric($value)) {
                $properties[$key] = 'int';
            // phpcs:disable Generic.PHP.ForbiddenFunctions.Found
            } else if (\is_null($value)) {
            // phpcs:enable
                $properties[$key] = 'mixed';
                $nullValues[$config][] = $key;
            } else if (\is_string($value)) {
                $properties[$key] = 'string';
            } else {
                $properties[$key] = 'mixed';
            }
        }

        return $this->createConfigClass(
            config: $config,
            namespace: $namespace,
            properties: $properties,
            stubConfiguration: $stubConfiguration,
        );
    }

    private function getStub(): string
    {
        $relativePath = '/stubs/typed_config/default.stub';

        return \file_exists($customPath = base_path(\trim($relativePath, '/')))
            ? $customPath
            : __DIR__ . $relativePath;
    }

    /**
     * @param array<string, string> $properties
     */
    private function buildProperties(array $properties): string
    {
        $properties = \array_map(
            fn (string $type, string $name): string =>
                \sprintf(
                    '%spublic %s $%s,',
                    \str_repeat(' ', 8),
                    $type,
                    Str::camel($name),
                ),
            $properties,
            \array_keys($properties),
        );

        return \implode(PHP_EOL, $properties);
    }

    /**
     * @param array<string, string> $properties
     * @throws FilesystemException
     */
    private function createConfigClass(
        string $config,
        string $namespace,
        array $properties,
        Config $stubConfiguration,
    ): string {
        $stub = \Safe\file_get_contents($this->getStub());

        $configParts = \explode('.', $config);
        // The last part is popped because we don't need it in the namespace.
        $lastConfigPart = \array_pop($configParts);
        $classPart = \ucfirst(Str::camel($lastConfigPart));

        if (ReservedName::check($lastConfigPart)) {
            $classPart = $this->contextAwareClassNaming(
                classPart: $classPart,
                configParts: $configParts,
            );
        }

        $classNamespace = \implode(
            '\\',
            \array_map(
                fn (string $configPart): string => \ucfirst(Str::camel($configPart)),
                $configParts,
            ),
        );

        if (!$stubConfiguration->useFinal) {
            $stub = \str_replace(
                search:  'final ',
                replace: '',
                subject: $stub,
            );
        }

        if (!$stubConfiguration->useReadonly) {
            $stub = \str_replace(
                search: 'readonly ',
                replace: '',
                subject: $stub,
            );
        }

        if (!$stubConfiguration->useStrict) {
            $stub = \str_replace(
                search: 'declare(strict_types=1);' . PHP_EOL . PHP_EOL,
                replace: '',
                subject: $stub,
            );
        }

        $stub = \str_replace(
            search: [
                '{{ namespace }}',
                '{{ properties }}',
                '{{ class }}',
                '{{ parent }}',
            ],
            replace: [
                \rtrim(
                    \sprintf(
                        '%s\\%s',
                        $namespace,
                        $classNamespace,
                    ),
                    '\\',
                ),
                $this->buildProperties($properties),
                $classPart,
                '\\' . TypedConfig::class,
            ],
            subject: $stub,
        );

        $this->writeClass(
            $stub,
            $config,
            $namespace,
        );

        return '\\' . $namespace . '\\' . $classNamespace . '\\' . $classPart;
    }

    private function writeClass(
        string $stub,
        string $config,
        string $namespace,
    ): void {
        $configParts = \explode('.', $config);
        // The last part is popped because we don't need it in the class directory path.
        $lastConfigPart = \array_pop($configParts);
        $classPart = \ucfirst(Str::camel($lastConfigPart));

        if (ReservedName::check($lastConfigPart)) {
            $classPart = $this->contextAwareClassNaming(
                classPart: $classPart,
                configParts: $configParts,
            );
        }

        $classDirectoryPath = \str_replace(
            search: app()->getNamespace(),
            replace: '',
            subject: $namespace,
        );
        $classDirectoryPath .= '/' . \implode(
            '/',
            \array_map(
                fn (string $configPart): string => \ucfirst(Str::camel($configPart)),
                $configParts,
            ),
        );

        $this->files->makeDirectory(
            path: app_path($classDirectoryPath),
            recursive: true,
            force: true,
        );

        $classPath = app_path(
            \sprintf(
                '%s/%s.php',
                $classDirectoryPath,
                $classPart,
            ),
        );

        $this->files->put($classPath, $stub);
    }

    private function skipDeepDive(
        string $config,
        string $key,
    ): bool {
        // Oh the irony...
        /* @phpstan-ignore-next-line */
        $skipDeepDives = config('typed_config_generator.skip_deep_dive');

        if (!\array_key_exists($config, $skipDeepDives)) {
            return false;
        }

        return \in_array($key, $skipDeepDives[$config]);
    }

    /**
     * @param array<int, string> $configParts
     */
    private function contextAwareClassNaming(
        string $classPart,
        array $configParts,
    ): string {
        $lastConfigPart = \array_reverse($configParts)[0];

        return \sprintf(
            '%s%s',
            \ucfirst(Str::camel($lastConfigPart)),
            $classPart,
        );
    }
}
