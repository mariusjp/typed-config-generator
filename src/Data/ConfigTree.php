<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Data;

use Coderg33k\TypedConfigGenerator\Enums\PropertyType;
use Illuminate\Support\Collection;

final readonly class ConfigTree
{
    /**
     * @param Collection<int, ConfigTree> $branches
     */
    private function __construct(
        public string $config,
        public mixed $value,
        public PropertyType $type,
        public bool $isNull,
        public Collection $branches,
    ) {
    }

    public static function create(
        string $config,
        mixed $value,
        PropertyType $type = PropertyType::Array,
    ): self {
        return new self(
            config: $config,
            value: $value,
            type: $type,
            isNull: false,
            branches: Collection::make(),
        );
    }

    public function addBranch(
        ConfigTree $configTree,
    ): void {
        $this->branches->push($configTree);
    }

    public function createAndPushBranch(
        string $config,
        mixed $value,
        PropertyType $type,
        bool $isNull = false,
        Collection $branches = new Collection(),
    ): void {
        $this->branches->push(
            new ConfigTree(
                config: $config,
                value: $value,
                type: $type,
                isNull: $isNull,
                branches: $branches,
            ),
        );
    }
}
