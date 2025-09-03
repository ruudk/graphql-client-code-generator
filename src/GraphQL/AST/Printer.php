<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL\AST;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\OperationDefinitionNode;
use Override;

final class Printer extends \GraphQL\Language\Printer
{
    #[Override]
    protected static function p(?Node $node) : string
    {
        if ($node instanceof OperationDefinitionNode) {
            $value = parent::p($node);

            if (str_starts_with($value, '{')) {
                return 'query ' . $value;
            }

            return $value;
        }

        return parent::p($node);
    }
}
