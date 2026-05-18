<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use Ruudk\GraphQLCodeGenerator\Planner\Source\GraphQLFileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineFragmentSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\TwigFileSource;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class DataClassPlan
{
    /**
     * Set of hook names this class transitively needs. Populated by the planner.
     * Empty means this class does not accept a `$hooks` constructor argument.
     *
     * @var array<string, true>
     */
    public array $usedHooks = [];

    /**
     * @param list<string> $possibleTypes
     * @param array<string, list<string>> $inlineFragmentRequiredFields
     */
    public function __construct(
        public readonly GraphQLFileSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
        public readonly string $path,
        public readonly string $fqcn,
        public readonly NamedType & Type $parentType,
        public readonly SymfonyType $fields,
        public readonly SymfonyType $payloadShape,
        public readonly array $possibleTypes,
        public readonly null | FragmentDefinitionNode | InlineFragmentNode | OperationDefinitionNode $definitionNode,
        public readonly ?SymfonyType $nodesType,
        public readonly array $inlineFragmentRequiredFields,
        public readonly bool $isData,
        public readonly bool $isFragment,
        /**
         * True when the only selection on this class is an explicitly
         * queried `__typename` (a first-level "fire and forget" mutation
         * field, a list field, or an inline fragment such as
         * `... on SupportedCountryError { __typename }`). The value is not
         * read back, so the generated `__typename` property is tagged
         * `@api` to keep dead-code analysis from flagging it.
         */
        public readonly bool $markTypenameAsApi = false,
    ) {}
}
