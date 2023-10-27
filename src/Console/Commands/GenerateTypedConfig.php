<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Console\Commands;

use Coderg33k\TypedConfigGenerator\Actions\GetConfigsForPredeterminedPackage;
use Coderg33k\TypedConfigGenerator\Enums\Package;
use Coderg33k\TypedConfigGenerator\Helper\ArrayFlatMap;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

#[AsCommand(name: 'coderg33k:generate-typed-config')]
final class GenerateTypedConfig extends Command
{
    private const GENERATED_CONFIG_NAMESPACE_BASE = 'Config';

    private array $configs = [];

    private ?string $package = null;

    /** @var string */
    protected $signature = 'coderg33k:generate-typed-config
                    {--all : Generate guestimated classes for all configurations}
                    {--config=* : One or more configurations to generate classes for}';

    /** @var string */
    protected $description =
<<<EOF
Generate all your configs as typed (guestimated) classes.
  Without input the command will generate all configs.
  It can also auto discover config files from packages.
EOF;

    public function __construct(
        private readonly Filesystem $files,
        private readonly GetConfigsForPredeterminedPackage $getConfigsForPredeterminedPackage,
        private readonly ArrayFlatMap $arrayFlatMap,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->determineWhatShouldBeGenerated();

        $this->generateTypedClasses();
    }

    private function determineWhatShouldBeGenerated(): void
    {
        if ($this->option('all')) {
            return;
        }

        $this->configs = (array) $this->option('config');

        if (\count($this->configs) === 0) {
            $this->promptForConfigs();
        }
    }

    private function setupChoices(): array
    {
        return \array_merge(
            ['All configurations'],
            \preg_filter('/^/', '<fg=gray>Package: </> ', Arr::sort(['Laravel', 'Spatie'])),
            \preg_filter('/^/', '<fg=gray>Config: </> ', Arr::sort(\array_keys(config()->all()))),
        );
    }

    private function promptForConfigs(): void
    {
        $choices = $this->setupChoices();

        $choice = windows_os()
            ? select(
                label: 'For which config do you want to generate a typed (guestimated) class?',
                options: $choices,
                scroll: 15,
            )
            : search(
                label: 'For which config do you want to generate a typed (guestimated) class?',
                options: fn ($search) => \array_values(\array_filter(
                    $choices,
                    fn ($choice) => \str_contains(\strtolower($choice), \strtolower($search))
                )),
                placeholder: 'Search...',
                scroll: 15,
            );

        if ($choice == $choices[0] || !\is_string($choice)) {
            return;
        }

        $this->parseChoice($choice);
    }

    private function parseChoice(string $choice): void
    {
        [$type, $value] = \explode(': ', \strip_tags($choice));

        switch ($type) {
            case 'Config':
                $this->configs = [$value];
                break;
            case 'Package':
                $this->package = $value;
                break;
        }
    }

    private function generateTypedClasses(): void
    {
        if (\count($this->configs) === 0 && !\is_string($this->package)) {
            $this->configs = [\implode(',', \array_keys(config()->all()))];
        }

        if (\is_string($this->package)) {
            $this->discoverConfigsForPackage();
        }

        $configsToProcess = \explode(',', $this->configs[0]);

        // Clean up configs.
        $configsToProcess = \array_map(
            fn (string $config): string => \trim($config),
            $configsToProcess,
        );

        $rootNamespace = $this->laravel->getNamespace();
        $namespace = $rootNamespace . self::GENERATED_CONFIG_NAMESPACE_BASE;

        // @todo: Get known namespaces for package configs.

        $nullValues = [];
        $properties = [];
        foreach ($configsToProcess as $config) {
            $configData = config($config);

            foreach ($configData as $key => $value) {
                // Let's start guestimating...
                if (\is_array($value)) {
                    $properties[$key] = 'array';
                } else if (\is_bool($value)) {
                    $properties[$key] = 'bool';
                } else if (\is_float($value)) {
                    $properties[$key] = 'float';
                } else if (\is_int($value)) {
                    $properties[$key] = 'int';
                } else if (\is_null($value)) {
                    $properties[$key] = 'mixed';
                    $nullValues[$config][] = $key;
                } else if (\is_string($value)) {
                    $properties[$key] = 'string';
                } else {
                    $properties[$key] = 'mixed';
                }
            }

            $this->buildStub(
                config: $config,
                namespace: $namespace,
                properties: $properties,
            );
        }

        if (\count($nullValues) > 0) {
            $this->components->warn('Some properties have null values, please check the generated class(es).');
            $this->table(
                ['Config', 'Key'],
                $this->arrayFlatMap->execute($nullValues),
            );
        }

        $this->newLine();
        $this->line('Class(es) created successfully!', 'info');
    }

    private function discoverConfigsForPackage(): void
    {
        $allConfigs = \array_keys(config()->all());

        $this->configs = [
            \implode(
                ',',
                \array_intersect(
                    $this->getConfigsForPredeterminedPackage->execute(
                        package: Package::getByValue($this->package),
                    ),
                    $allConfigs,
                ),
            )
        ];
    }

    private function buildStub(
        string $config,
        string $namespace,
        array $properties,
    ): void {
        $stub = \file_get_contents($this->getStub());

        $stub = \str_replace(
            search: [
                '{{ namespace }}',
                '{{ properties }}',
                '{{ class }}',
                '{{ parent }}',
            ],
            replace: [
                $namespace,
                $this->buildProperties($properties),
                \ucfirst(Str::camel($config)),
                '\\' . \Coderg33k\TypedConfigGenerator\TypedConfig::class,
            ],
            subject: $stub,
        );

        $this->writeClass(
            $stub,
            $config,
            $namespace,
        );
    }

    private function getStub(): string
    {
        $relativePath = '/stubs/typed_config/default.stub';

        return \file_exists($customPath = $this->laravel->basePath(\trim($relativePath, '/')))
            ? $customPath
            : __DIR__ . $relativePath;
    }

    private function buildProperties(array $properties): string
    {
        $properties = \array_map(
            fn (string $type, string $name): string =>
                \sprintf('        public %s $%s,', $type, Str::camel($name)),
            $properties,
            \array_keys($properties),
        );

        return \implode(PHP_EOL, $properties);
    }

    private function writeClass(
        string $stub,
        string $config,
        string $namespace,
    ): void {
        $classDirectoryPath = \str_replace($this->laravel->getNamespace(), '', $namespace);

        $this->files->makeDirectory(
            path: app_path($classDirectoryPath),
            recursive: true,
            force: true,
        );

        $classPath = app_path(
            \sprintf(
                '%s/%s.php',
                $classDirectoryPath,
                \ucfirst(Str::camel($config)),
            )
        );

        $this->files->put($classPath, $stub);
    }
}
