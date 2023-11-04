<?php

declare(strict_types=1);

namespace Coderg33k\TypedConfigGenerator\Helper;

final class GetNamespaceOfClassFile
{
    /**
     * @param class-string $className
     */
    public static function execute(
        string $className,
        string $searchPath = '/',
    ): string {
        $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($searchPath);
        $recursiveIteratorIterator = new \RecursiveIteratorIterator($recursiveDirectoryIterator);

        $regexIterator = new \RegexIterator(
            iterator: $recursiveIteratorIterator,
            pattern: '/' . $className . '\.php/',
            mode: \RegexIterator::GET_MATCH,
        );

        $iteratorArray = \iterator_to_array($regexIterator);

        $classPath = \key($iteratorArray);

        $fp = \fopen($classPath, 'r');
        $fqn = '';
        $buffer = '';

        $i = 0;
        while (\strlen($fqn) === 0) {
            if (\feof($fp)) {
                break;
            }

            $buffer .= \fread($fp, 512);
            $tokens = \token_get_all($buffer);

            if (!\str_contains($buffer, '{')) {
                continue;
            }

            for (; $i < \count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = ($i + 1); $j < \count($tokens); $j++) {
                        if ($tokens[$j][0] === T_NAME_QUALIFIED) {
                            $fqn = $tokens[$j][1];
                            break;
                        } else if ($tokens[$j][0] === T_STRING) {
                            $fqn = $tokens[$j][1];
                            break;
                        } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                }
            }
        }

        $basename = \array_reverse(\explode('\\', $fqn));
        \array_shift($basename);

        return $basename[0];
    }
}
