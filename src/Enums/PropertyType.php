<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Enums;

enum PropertyType: string
{
    case Array = 'array';
    case Boolean = 'bool';
    case Float = 'float';
    case Integer = 'int';
    case String = 'string';
    case Unknown = 'mixed';
}
