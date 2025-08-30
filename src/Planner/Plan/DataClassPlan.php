<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class DataClassPlan
{
    /**
     * @param list<string> $possibleTypes
     * @param array<string, list<string>> $inlineFragmentRequiredFields
     */
    public function __construct(
        public string $source,
        public string $path,
        public string $fqcn,
        public NamedType & Type $parentType,
        public SymfonyType $fields,
        public SymfonyType $payloadShape,
        public array $possibleTypes,
        public null | FragmentDefinitionNode | InlineFragmentNode | OperationDefinitionNode $definitionNode,
        public ?SymfonyType $nodesType,
        public array $inlineFragmentRequiredFields,
        public bool $isData,
        public bool $isFragment,
    ) {}
}
