<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class IncludeAndSkipDirectiveOptimizer
{
    /**
     * @template T of Node
     * @param T $node
     * @param array<string, array{FragmentDefinitionNode, list<string>}> $fragmentDefinitions
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node, array $fragmentDefinitions) : Node
    {
        $new = Visitor::visit($node, [
            NodeKind::FIELD => [
                'enter' => function (Node $node) {
                    Assert::isInstanceOf($node, FieldNode::class);

                    if ($node->directives->count() === 0) {
                        return null;
                    }

                    $changed = false;
                    foreach ($node->directives as $index => $directive) {
                        if ( ! in_array($directive->name->value, ['include', 'skip'], true)) {
                            continue;
                        }

                        $targetValue = $directive->name->value === 'include';

                        if ($directive->arguments->count() === 0) {
                            continue;
                        }

                        $argument = $directive->arguments[0];

                        if ($argument->name->value !== 'if') {
                            continue;
                        }

                        if ( ! $argument->value instanceof BooleanValueNode) {
                            continue;
                        }

                        if ($argument->value->value === $targetValue) {
                            unset($node->directives[$index]);
                            $node->directives->reindex();
                            $changed = true;

                            break;
                        }

                        return Visitor::removeNode();
                    }

                    if ( ! $changed) {
                        return null;
                    }

                    return $node;
                },
            ],
        ]);

        Assert::isInstanceOf($new, Node::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }
}
