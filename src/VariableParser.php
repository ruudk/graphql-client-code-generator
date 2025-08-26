<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\OperationDefinitionNode;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class VariableParser
{
    public function __construct(
        private readonly TypeMapper $typeMapper,
    ) {}

    /**
     * Parse GraphQL operation variables into PHP types
     * @throws InvariantViolation
     * @return array<string, SymfonyType>
     */
    public function parseVariables(OperationDefinitionNode $operation) : array
    {
        $required = [];
        $optional = [];

        foreach ($operation->variableDefinitions as $varDef) {
            $name = $varDef->variable->name->value;
            $type = $this->typeMapper->mapGraphQLASTTypeToPHPType($varDef->type);

            if ($type instanceof SymfonyType\NullableType) {
                $optional[$name] = $type;

                continue;
            }

            $required[$name] = $type;
        }

        return [
            ...$required,
            ...$optional,
        ];
    }
}
