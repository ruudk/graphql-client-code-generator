<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL;

use GraphQL\Language\AST\FragmentDefinitionNode;

final class FragmentDefinitionNodeWithSource extends FragmentDefinitionNode
{
    public string $source;

    public static function create(FragmentDefinitionNode $fragmentNode, string $source) : self
    {
        return new self([
            'name' => $fragmentNode->name,
            'variableDefinitions' => $fragmentNode->variableDefinitions,
            'typeCondition' => $fragmentNode->typeCondition,
            'directives' => $fragmentNode->directives,
            'selectionSet' => $fragmentNode->selectionSet,
            'source' => $source,
        ]);
    }
}
