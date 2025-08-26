<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Utility;

use Symfony\Component\TypeInfo\Type;

/**
 * Formats Symfony Type objects as PHPDoc type strings
 */
final class PayloadShapeFormatter
{
    /**
     * Format a Type as a PHPDoc string
     */
    public function format(Type $type, int $indentation = 0) : string
    {
        return $this->dumpPHPDocType($type, fn(string $class) => $class, $indentation);
    }

    /**
     * @param callable(string): string $importer
     */
    private function dumpPHPDocType(Type $type, callable $importer, int $indentation = 0) : string
    {
        if ($type instanceof Type\NullableType) {
            return sprintf('null|%s', $this->dumpPHPDocType($type->getWrappedType(), $importer, $indentation));
        }

        if ($type instanceof Type\ArrayShapeType) {
            $items = [];
            $shape = $type->getShape();

            // Sort fields alphabetically for consistent output
            ksort($shape);

            foreach ($shape as $key => ['type' => $itemType, 'optional' => $optional]) {
                $itemKey = sprintf("'%s'", $key);

                if ($optional) {
                    $itemKey = sprintf('%s?', $itemKey);
                }

                $items[] = sprintf('%s: %s', $itemKey, $this->dumpPHPDocType($itemType, $importer, $indentation + 1));
            }

            if ($items === []) {
                return 'array{}';
            }

            $pad = $indentation === 0 ? '' : str_repeat(' ', $indentation * 4);

            return sprintf(
                "array{\n%s    %s,\n%s}",
                $pad,
                implode(sprintf(",\n%s    ", $pad), $items),
                $pad,
            );
        }

        if ($type instanceof Type\CollectionType) {
            if ($type->isList()) {
                return sprintf('list<%s>', $this->dumpPHPDocType($type->getCollectionValueType(), $importer, $indentation));
            }

            return sprintf(
                'array<%s,%s>',
                $this->dumpPHPDocType($type->getCollectionKeyType(), $importer, $indentation),
                $this->dumpPHPDocType($type->getCollectionValueType(), $importer, $indentation),
            );
        }

        if ($type instanceof Type\UnionType) {
            return implode(
                '|',
                array_unique(
                    array_map(
                        fn(Type $type) => $this->dumpPHPDocType($type, $importer, $indentation),
                        $type->getTypes(),
                    ),
                ),
            );
        }

        if ($type instanceof Type\ObjectType) {
            return $importer($type->getClassName());
        }

        return $type->__toString();
    }
}
