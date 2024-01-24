<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Helper\Console\Command\Table;

final class GetRowsFromAssociativeArray
{
    /**
     * @param array<int, string>|array<string, string|array<int, string>> $array
     * @return array<int, array<int, array<int, string>|string>|string>
     */
    public function execute(
        array $array,
        string $prefix = '',
    ): array {
        $result = [];

        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $result = \array_merge($result, $this->execute($value, \strval($key)));
            } else {
                $result[] = [
                    $prefix,
                    $value,
                ];
            }
        }

        return $result;
    }
}
