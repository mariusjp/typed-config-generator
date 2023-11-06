<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Support\Stub;

use Coderg33k\TypedConfigGenerator\Helper\IsArrayConfiguration;
use Coderg33k\TypedConfigGenerator\TypedConfig;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;

final class Builder
{
    /**
     * Copied from Illuminate\Console\GeneratorCommand.
     * @var array<int, string>
     */
    protected array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'enum',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'readonly',
        'require',
        'require_once',
        'return',
        'self',
        'static',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        '__CLASS__',
        '__DIR__',
        '__FILE__',
        '__FUNCTION__',
        '__LINE__',
        '__METHOD__',
        '__NAMESPACE__',
        '__TRAIT__',
    ];

    public function __construct(
        private readonly Application $laravel,
        private readonly Filesystem $files,
    ) {
    }

    /**
     * @param array<string, array<int, string>> $nullValues
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

    private function determineProperties(
        string $config,
        string $namespace,
        array &$nullValues,
        Config $stubConfiguration,
    ): string {
        $properties = [];
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

                if ($this->specialCase($config, $key)) {
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

    private function createConfigClass(
        string $config,
        string $namespace,
        array $properties,
        Config $stubConfiguration,
    ): string {
        $stub = \file_get_contents($this->getStub());

        $configParts = \explode('.', $config);
        // The last part is popped because we don't need it in the namespace.
        $lastConfigPart = \array_pop($configParts);
        $classPart = \ucfirst(Str::camel($lastConfigPart));

        if ($this->isReservedWord($lastConfigPart)) {
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

        if ($this->isReservedWord($lastConfigPart)) {
            $classPart = $this->contextAwareClassNaming(
                classPart: $classPart,
                configParts: $configParts,
            );
        }

        $classDirectoryPath = \str_replace(
            search: $this->laravel->getNamespace(),
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

    private function specialCase(
        string $config,
        string $key,
    ): bool {
        $specialCases = [
            'app' => [
                'providers',
                'aliases',
            ],
            'cache' => [
                'servers',
            ],
        ];

        // @todo: Merge with user inputted special case?

        if (!\array_key_exists($config, $specialCases)) {
            return false;
        }

        return \in_array($key, $specialCases[$config]);
    }

    private function isReservedWord(string $configPart): bool {
        if (!\in_array(\strtolower($configPart), $this->reservedNames)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<int, string> $configParts
     */
    private function contextAwareClassNaming(
        string $classPart,
        array $configParts,
    ) {
        $lastConfigPart = \array_reverse($configParts)[0];

        return \sprintf(
            '%s%s',
            \ucfirst(Str::camel($lastConfigPart)),
            $classPart,
        );
    }
}
