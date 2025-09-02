<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL;

use GraphQL\Language\AST\FragmentDefinitionNode;
use Ruudk\GraphQLCodeGenerator\Planner\Source\FileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;

final class FragmentDefinitionNodeWithSource extends FragmentDefinitionNode
{
    public FileSource | InlineSource $source;

    public static function create(FragmentDefinitionNode $fragmentNode, FileSource | InlineSource $source) : self
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
