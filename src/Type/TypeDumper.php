<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Symfony\Component\TypeInfo\Type;

final class TypeDumper
{
    /**
     * @param callable(string): string $importer
     */
    public static function dump(Type $type, ?callable $importer = null, int $indentation = 0) : string
    {
        $importer ??= fn(string $class) => $class;

        if ($type instanceof Type\NullableType) {
            return sprintf('null|%s', self::dump($type->getWrappedType(), $importer, $indentation));
        }

        if ($type instanceof Type\ArrayShapeType) {
            $items = [];

            foreach ($type->getShape() as $key => ['type' => $itemType, 'optional' => $optional]) {
                $itemKey = sprintf("'%s'", $key);

                if ($optional) {
                    $itemKey = sprintf('%s?', $itemKey);
                }

                $items[] = sprintf('%s: %s', $itemKey, self::dump($itemType, $importer, $indentation + 1));
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
                return sprintf('list<%s>', self::dump($type->getCollectionValueType(), $importer, $indentation));
            }

            return sprintf(
                'array<%s, %s>',
                self::dump($type->getCollectionKeyType(), $importer, $indentation),
                self::dump($type->getCollectionValueType(), $importer, $indentation),
            );
        }

        if ($type instanceof Type\UnionType) {
            return implode(
                '|',
                array_unique(
                    array_map(
                        fn(Type $type) => self::dump($type, $importer, $indentation),
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
