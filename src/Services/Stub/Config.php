<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Services\Stub;

final readonly class Config
{
    private function __construct(
        public readonly bool $useFlat,
        public readonly bool $useStrict,
        public readonly bool $useFinal,
        public readonly bool $useReadonly,
    ) {
    }

    public static function make(
        bool $useFlat,
        bool $useStrict,
        bool $useFinal,
        bool $useReadonly,
    ): self {
        return new self(
            useFlat: $useFlat,
            useStrict: $useStrict,
            useFinal: $useFinal,
            useReadonly: $useReadonly,
        );
    }
}
