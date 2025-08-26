<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Error\InvariantViolation;
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
use GraphQL\Type\Schema;
use Ruudk\GraphQLCodeGenerator\Type\ScalarType as CustomScalarType;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class TypeMapper
{
    /**
     * @param array<string, array{SymfonyType, SymfonyType}> $scalars
     * @param array<string, SymfonyType> $enumTypes
     * @param array<string, SymfonyType> $inputObjectTypes
     * @param array<string, array{SymfonyType, SymfonyType}> $objectTypes
     */
    public function __construct(
        private Schema $schema,
        private array $scalars,
        private array $enumTypes,
        private array $inputObjectTypes,
        private array $objectTypes,
    ) {}

    /**
     * @throws InvariantViolation
     */
    public function mapGraphQLASTTypeToPHPType(TypeNode $typeNode, ?bool $nullable = null) : SymfonyType
    {
        if ($typeNode instanceof NonNullTypeNode) {
            return $this->mapGraphQLASTTypeToPHPType($typeNode->type, false);
        }

        if ($nullable === null) {
            return SymfonyType::nullable($this->mapGraphQLASTTypeToPHPType($typeNode, true));
        }

        if ($typeNode instanceof ListTypeNode) {
            return SymfonyType::list($this->mapGraphQLASTTypeToPHPType($typeNode->type));
        }

        if ($typeNode instanceof NamedTypeNode) {
            if (isset($this->scalars[$typeNode->name->value])) {
                return $this->scalars[$typeNode->name->value][1];
            }

            if (isset($this->enumTypes[$typeNode->name->value])) {
                return $this->enumTypes[$typeNode->name->value];
            }

            if (isset($this->inputObjectTypes[$typeNode->name->value])) {
                return $this->inputObjectTypes[$typeNode->name->value];
            }

            if (isset($this->objectTypes[$typeNode->name->value])) {
                return $this->objectTypes[$typeNode->name->value][1];
            }

            // TODO: should we use this for everything?
            $type = $this->schema->getType($typeNode->name->value);

            if ($type instanceof ScalarType) {
                return $this->scalars[$type->name()][1] ?? new CustomScalarType();
            }

            return SymfonyType::mixed();
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

            return new CustomScalarType();
        }

        if ($type instanceof EnumType && $builtInOnly) {
            return SymfonyType::string();
        }

        if ($type instanceof NamedType) {
            if ( ! $builtInOnly) {
                if (isset($this->enumTypes[$type->name()])) {
                    return $this->enumTypes[$type->name()];
                }

                if (isset($this->inputObjectTypes[$type->name()])) {
                    return $this->inputObjectTypes[$type->name()];
                }
            }

            if (isset($this->objectTypes[$type->name()])) {
                return $builtInOnly ? $this->objectTypes[$type->name()][0] : $this->objectTypes[$type->name()][1];
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
}
