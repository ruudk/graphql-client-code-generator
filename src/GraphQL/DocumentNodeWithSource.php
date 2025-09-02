<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\NodeList;
use Ruudk\GraphQLCodeGenerator\Planner\Source\FileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;

final class DocumentNodeWithSource extends DocumentNode
{
    public FileSource | InlineSource $source;

    public static function create(DocumentNode $documentNode, FileSource | InlineSource $source) : self
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
