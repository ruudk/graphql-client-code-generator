<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\NodeList;
use Ruudk\GraphQLCodeGenerator\Planner\Source\GraphQLFileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\TwigFileSource;

final class DocumentNodeWithSource extends DocumentNode
{
    public GraphQLFileSource | InlineSource | TwigFileSource $source;

    public static function create(DocumentNode $documentNode, GraphQLFileSource | InlineSource | TwigFileSource $source) : self
    {
        $definitions = [];
        foreach ($documentNode->definitions as $definition) {
            if ($definition instanceof FragmentDefinitionNode) {
                $definition = FragmentDefinitionNodeWithSource::create($definition, $source);
            }

            $definitions[] = $definition;
        }

        return new self([
            'definitions' => new NodeList($definitions),
            'source' => $source,
        ]);
    }
}
