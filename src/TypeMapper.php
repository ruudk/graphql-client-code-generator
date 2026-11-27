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
use Ruudk\GraphQLCodeGenerator\Type\TypeHelper;
use Stringable;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\TypeIdentifier;

final readonly class TypeMapper
{
    /**
     * @param array<string, array{SymfonyType, SymfonyType}> $scalars
     * @param array<string, SymfonyType> $enumTypes
     * @param array<string, SymfonyType> $inputObjectTypes
     * @param array<string, array{SymfonyType, SymfonyType}> $objectTypes
     * @param array<string, SymfonyType> $scalarInputTypes
     */
    public function __construct(
        private Schema $schema,
        private array $scalars,
        private array $enumTypes,
        private array $inputObjectTypes,
        private array $objectTypes,
        private array $scalarInputTypes = [],
    ) {}

    /**
     * The PHP type the generated code takes for a variable. A string also takes a Stringable, which the
     * generated code casts, unless the scalar has an input type of its own.
     *
     * @throws InvariantViolation
     */
    public function mapGraphQLASTTypeToPHPInputType(TypeNode $typeNode): SymfonyType
    {
        $type = $this->mapGraphQLASTTypeToPHPType($typeNode, input: true);

        if ($typeNode instanceof NonNullTypeNode) {
            $typeNode = $typeNode->type;
        }

        if ($typeNode instanceof NamedTypeNode && isset($this->scalarInputTypes[$typeNode->name->value])) {
            return $type;
        }

        return self::acceptStringable($type);
    }

    /**
     * The PHP type the generated code takes for a field of an input object. A string also takes a Stringable,
     * which the generated code casts, unless the scalar has an input type of its own.
     */
    public function mapGraphQLTypeToPHPInputType(Type $type): SymfonyType
    {
        $phpType = $this->mapGraphQLTypeToPHPType($type, input: true);

        $namedType = Type::getNullableType($type);

        if ($namedType instanceof ScalarType && isset($this->scalarInputTypes[$namedType->name()])) {
            return $phpType;
        }

        return self::acceptStringable($phpType);
    }

    /**
     * @throws InvariantViolation
     */
    public function mapGraphQLASTTypeToPHPType(
        TypeNode $typeNode,
        ?bool $nullable = null,
        bool $input = false,
    ): SymfonyType {
        if ($typeNode instanceof NonNullTypeNode) {
            return $this->mapGraphQLASTTypeToPHPType($typeNode->type, false, $input);
        }

        if ($nullable === null) {
            return SymfonyType::nullable($this->mapGraphQLASTTypeToPHPType($typeNode, true, $input));
        }

        if ($typeNode instanceof ListTypeNode) {
            return SymfonyType::list($this->mapGraphQLASTTypeToPHPType($typeNode->type, input: $input));
        }

        if ($typeNode instanceof NamedTypeNode) {
            if ($input && isset($this->scalarInputTypes[$typeNode->name->value])) {
                return $this->scalarInputTypes[$typeNode->name->value];
            }

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

            // TODO(@ruudk): should we use this for everything?
            $type = $this->schema->getType($typeNode->name->value);

            if ($type instanceof ScalarType) {
                return $this->scalars[$type->name()][1] ?? new CustomScalarType();
            }

            return SymfonyType::mixed();
        }

        return SymfonyType::mixed();
    }

    public function mapGraphQLTypeToPHPType(
        Type $type,
        ?bool $nullable = null,
        bool $builtInOnly = false,
        bool $input = false,
    ): SymfonyType {
        if ($type instanceof NonNull) {
            return $this->mapGraphQLTypeToPHPType($type->getWrappedType(), false, $builtInOnly, $input);
        }

        if ($nullable === null) {
            return SymfonyType::nullable($this->mapGraphQLTypeToPHPType($type, true, $builtInOnly, $input));
        }

        if ($type instanceof ListOfType) {
            return SymfonyType::list($this->mapGraphQLTypeToPHPType(
                $type->getWrappedType(),
                null,
                $builtInOnly,
                $input,
            ));
        }

        if ($type instanceof ScalarType) {
            if ($input && !$builtInOnly && isset($this->scalarInputTypes[$type->name()])) {
                return $this->scalarInputTypes[$type->name()];
            }

            if (isset($this->scalars[$type->name()])) {
                return $builtInOnly ? $this->scalars[$type->name()][0] : $this->scalars[$type->name()][1];
            }

            return new CustomScalarType();
        }

        if ($type instanceof EnumType && $builtInOnly) {
            return SymfonyType::string();
        }

        if ($type instanceof NamedType) {
            if (!$builtInOnly) {
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

    private static function acceptStringable(SymfonyType $type): SymfonyType
    {
        if (!$type->isIdentifiedBy(TypeIdentifier::STRING)) {
            return $type;
        }

        return TypeHelper::rewrap($type, static fn() => SymfonyType::union(
            SymfonyType::string(),
            SymfonyType::object(Stringable::class),
        ));
    }
}
