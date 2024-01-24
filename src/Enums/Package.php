<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Enums;

enum Package: string
{
    case Laravel = 'laravel';
    case Spatie = 'spatie';

    public static function getByValue(string $value): self
    {
        return match (\trim(\strtolower($value))) {
            'laravel' => self::Laravel,
            'spatie' => self::Spatie,
            default => throw new \InvalidArgumentException('Invalid package value'),
        };
    }
}
