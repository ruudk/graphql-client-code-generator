<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Type\ScalarType;
use Symfony\Component\TypeInfo\Type;

abstract class AbstractGenerator
{
    public function __construct(
        protected readonly Config $config,
    ) {}

    protected function dumpHeader() : string
    {
        return '// This file was automatically generated and should not be edited.';
    }

    protected function fullyQualified(string $part, string ...$moreParts) : string
    {
        if (str_starts_with($part, $this->config->namespace . '\\')) {
            $part = substr($part, strlen($this->config->namespace) + 1);
        }

        return implode('\\', array_filter([$this->config->namespace, $part, ...$moreParts], fn($part) => $part !== ''));
    }

    /**
     * @param callable(string): string $importer
     */
    protected function dumpPHPType(Type $type, callable $importer) : string
    {
        if ($type instanceof Type\NullableType) {
            $wrappedType = $type->getWrappedType();

            if ($wrappedType instanceof Type\CollectionType || ! $wrappedType instanceof Type\WrappingTypeInterface) {
                return sprintf('?%s', $this->dumpPHPType($wrappedType, $importer));
            }

            return sprintf('null|%s', $this->dumpPHPType($wrappedType, $importer));
        }

        if ($type instanceof ScalarType) {
            return 'int|string|float|bool';
        }

        if ($type instanceof Type\CollectionType) {
            return 'array';
        }

        if ($type instanceof Type\ObjectType) {
            return $importer($type->getClassName());
        }

        if ($type instanceof Type\UnionType) {
            return implode(
                '|',
                array_unique(
                    array_map(
                        fn(Type $type) => $this->dumpPHPType($type, $importer),
                        $type->getTypes(),
                    ),
                ),
            );
        }

        return (string) $type;
    }

    /**
     * @param callable(string): string $importer
     */
    protected function dumpPHPDocType(Type $type, callable $importer, int $indentation = 0) : string
    {
        if ($type instanceof Type\NullableType) {
            return sprintf('null|%s', $this->dumpPHPDocType($type->getWrappedType(), $importer, $indentation));
        }

        if ($type instanceof Type\ArrayShapeType) {
            $items = [];

            foreach ($type->getShape() as $key => ['type' => $itemType, 'optional' => $optional]) {
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

        return (string) $type;
    }

    protected function getNakedType(Type $type) : Type
    {
        if ($type instanceof Type\NullableType) {
            return $type->getWrappedType();
        }

        return $type;
    }
}
