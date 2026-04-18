<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Type\HookPropertyType;
use Ruudk\GraphQLCodeGenerator\Type\ScalarType;
use Symfony\Component\TypeInfo\Type;
use Webmozart\Assert\Assert;

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
        if ($type instanceof HookPropertyType) {
            return $this->dumpPHPType($type->getWrappedType(), $importer);
        }

        if ($type instanceof Type\NullableType) {
            $wrappedType = $type->getWrappedType();

            if ($wrappedType instanceof Type\UnionType || $wrappedType instanceof ScalarType) {
                return sprintf('null|%s', $this->dumpPHPType($wrappedType, $importer));
            }

            return sprintf('?%s', $this->dumpPHPType($wrappedType, $importer));
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

    protected function getNakedType(Type $type) : Type
    {
        if ($type instanceof Type\NullableType) {
            return $type->getWrappedType();
        }

        return $type;
    }

    /**
     * Build the PHPDoc `array{hookName: HookClass, ...}` shape used to annotate
     * the `$hooks` constructor argument threaded through generated classes.
     *
     * @param array<string, true> $usedHooks
     * @throws \Webmozart\Assert\InvalidArgumentException
     */
    protected function buildHooksShape(array $usedHooks) : Type
    {
        $shape = [];

        foreach (array_keys($usedHooks) as $name) {
            Assert::keyExists(
                $this->config->hooks,
                $name,
                sprintf('Hook "%s" is not registered in config.', $name),
            );
            $shape[$name] = Type::object($this->config->hooks[$name]->class);
        }

        return Type::arrayShape($shape);
    }
}
