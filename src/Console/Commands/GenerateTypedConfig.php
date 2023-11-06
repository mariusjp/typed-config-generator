<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Console\Commands;

use Coderg33k\TypedConfigGenerator\Actions\GetConfigsForPredeterminedPackage;
use Coderg33k\TypedConfigGenerator\Enums\Package;
use Coderg33k\TypedConfigGenerator\Helper\ArrayFlatMap;
use Coderg33k\TypedConfigGenerator\Support\Stub\Builder;
use Coderg33k\TypedConfigGenerator\Support\Stub\Config;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

#[AsCommand(name: 'coderg33k:generate-typed-config')]
final class GenerateTypedConfig extends Command
{
    private const GENERATED_CONFIG_NAMESPACE_BASE = 'Config';

    /** @var array<int, string> */
    private array $configs = [];
    private ?string $package = null;

    /** @var string */
    protected $signature = 'coderg33k:generate-typed-config
                    {--all : Generate guestimated classes for all configurations}
                    {--flat : Don\'t try to generate classes for nested configurations}
                    {--no-strict : Don\'t add declare(strict_types=1) to the generated classes}
                    {--no-final : Don\'t make the generated classes final}
                    {--no-readonly : Don\'t make the generated classes readonly}
                    {--package= : Generate classes for all configs in a package}
                    {--config=* : One or more configurations to generate classes for}';

    /** @var string */
    protected $description = <<<EOF
Generate all your configs as typed (guestimated) classes.
  Without input the command will generate all configs.
  It can also auto discover config files from packages.
EOF;

    public function __construct(
        private readonly GetConfigsForPredeterminedPackage $getConfigsForPredeterminedPackage,
        private readonly Builder $stubBuilder,
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

        if (\count($this->configs) === 0 && !\is_string($this->option('package'))) {
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

        $stubConfiguration = Config::make(
            $this->option('flat'),
            !$this->option('no-strict'),
            !$this->option('no-final'),
            !$this->option('no-readonly'),
        );

        // @todo: Get known namespaces for package configs.

        $nullValues = [];
        foreach ($configsToProcess as $config) {
            $this->stubBuilder->handle(
                config: $config,
                namespace: $namespace,
                nullValues: $nullValues,
                stubConfiguration: $stubConfiguration,
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
            ),
        ];
    }
}
