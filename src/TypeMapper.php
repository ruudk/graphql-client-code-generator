<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Webmozart\Assert\Assert;

final class TypeMapper
{
    /**
     * @param array<string, array{SymfonyType, SymfonyType}> $scalars
     * @param array<string, SymfonyType> $enumTypes
     * @param array<string, SymfonyType> $inputObjectTypes
     * @param array<string, array{SymfonyType, SymfonyType}> $objectTypes
     */
    public function __construct(
        private readonly array $scalars,
        private readonly array $enumTypes,
        private readonly array $inputObjectTypes,
        private readonly array $objectTypes,
    ) {}

    public function mapGraphQLASTTypeToPHPType(TypeNode $type, ?bool $nullable = null) : SymfonyType
    {
        if ($type instanceof NonNullTypeNode) {
            return $this->mapGraphQLASTTypeToPHPType($type->type, false);
        }

        if ($nullable === null) {
            return SymfonyType::nullable($this->mapGraphQLASTTypeToPHPType($type, true));
        }

        if ($type instanceof ListTypeNode) {
            return SymfonyType::list($this->mapGraphQLASTTypeToPHPType($type->type));
        }

        if ($type instanceof NamedTypeNode) {
            if (isset($this->scalars[$type->name->value])) {
                return $this->scalars[$type->name->value][1];
            }

            if (isset($this->enumTypes[$type->name->value])) {
                return $this->enumTypes[$type->name->value];
            }

            if (isset($this->inputObjectTypes[$type->name->value])) {
                return $this->inputObjectTypes[$type->name->value];
            }

            if (isset($this->objectTypes[$type->name->value])) {
                return $this->objectTypes[$type->name->value][1];
            }
        }

        return SymfonyType::mixed();
    }

    public function mapGraphQLTypeToPHPType(Type $type, ?bool $nullable = null, bool $builtInOnly = false) : SymfonyType
    {
        if ($type instanceof NonNull) {
            return $this->mapGraphQLTypeToPHPType($type->getWrappedType(), false, $builtInOnly);
        }

        if ($nullable === null) {
            return SymfonyType::nullable($this->mapGraphQLTypeToPHPType($type, true, $builtInOnly));
        }

        if ($type instanceof ListOfType) {
            return SymfonyType::list($this->mapGraphQLTypeToPHPType($type->getWrappedType(), $builtInOnly));
        }

        if ($type instanceof ScalarType) {
            if (isset($this->scalars[$type->name()])) {
                return $builtInOnly ? $this->scalars[$type->name()][0] : $this->scalars[$type->name()][1];
            }
        }

        if ($type instanceof EnumType && $builtInOnly) {
            return SymfonyType::string();
        }

        if ( ! $builtInOnly && $type instanceof NamedType) {
            if (isset($this->enumTypes[$type->name()])) {
                return $this->enumTypes[$type->name()];
            }

            if (isset($this->inputObjectTypes[$type->name()])) {
                return $this->inputObjectTypes[$type->name()];
            }

            if (isset($this->objectTypes[$type->name()])) {
                return $this->objectTypes[$type->name()][1];
            }
        }

        return SymfonyType::mixed();
    }

    public function getNakedType(SymfonyType $type) : SymfonyType
    {
        if ($type instanceof SymfonyType\NullableType) {
            return $type->getWrappedType();
        }

        return $type;
    }

    /**
     * @throws \Webmozart\Assert\InvalidArgumentException
     */
    public function mergeArrayShape(SymfonyType $left, SymfonyType $right) : SymfonyType
    {
        if ($right instanceof SymfonyType\NullableType) {
            $right = $right->getWrappedType();
        }

        if ($right instanceof SymfonyType\UnionType) {
            return SymfonyType::union($left, ...$right->getTypes());
        }

        Assert::isInstanceOf($left, ArrayShapeType::class, 'Left type must be an array shape, %s given');
        Assert::isInstanceOf($right, ArrayShapeType::class, 'Right type must be an array shape, %s given');

        $leftShape = $left->getShape();
        $rightShape = $right->getShape();
        $mergedShape = [];

        // Copy all fields from left shape
        foreach ($leftShape as $key => $value) {
            $mergedShape[$key] = $value;
        }

        // Merge fields from right shape
        foreach ($rightShape as $key => $value) {
            if (isset($mergedShape[$key])) {
                // Field exists in both shapes - need to merge
                $leftValue = $mergedShape[$key];

                // Extract the actual types from the array shape format
                $leftType = $leftValue['type'];
                $rightType = $value['type'];

                // If both are array shapes, merge them recursively
                if ($leftType instanceof ArrayShapeType && $rightType instanceof ArrayShapeType) {
                    $mergedType = $this->mergeArrayShape($leftType, $rightType);

                    $mergedShape[$key] = [
                        'type' => $mergedType,
                        'optional' => $leftValue['optional'],
                    ];

                    continue;
                }

                // For non-array shapes, just overwrite
                $mergedShape[$key] = $value;

                continue;
            }

            // Field only exists in right shape
            $mergedShape[$key] = $value;
        }

        return SymfonyType::arrayShape($mergedShape);
    }
}
