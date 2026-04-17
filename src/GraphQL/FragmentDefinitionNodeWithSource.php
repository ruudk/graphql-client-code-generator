<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL;

use GraphQL\Language\AST\FragmentDefinitionNode;
use Ruudk\GraphQLCodeGenerator\Planner\Source\GraphQLFileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\TwigFileSource;

final class FragmentDefinitionNodeWithSource extends FragmentDefinitionNode
{
    public GraphQLFileSource | InlineSource | TwigFileSource $source;

    public static function create(FragmentDefinitionNode $fragmentNode, GraphQLFileSource | InlineSource | TwigFileSource $source) : self
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
