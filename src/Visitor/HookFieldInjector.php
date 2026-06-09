<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Visitor;
use Ruudk\GraphQLCodeGenerator\Config\HookDefinition;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * Replaces every `@hook`-directive field with an inline fragment carrying the
 * hook's `requires` selection.
 *
 * A `@hook` field name (e.g. `approvalDecision`) is a generator-only marker — it
 * is not a real field on the parent GraphQL type. For the operation sent to the
 * server it is substituted with `... on Type { ...the data the hook needs... }`,
 * so the response carries exactly the fields the hook's data class reads.
 */
final readonly class HookFieldInjector
{
    /**
     * @param array<string, HookDefinition> $hooks
     */
    public function __construct(
        private array $hooks,
    ) {}

    /**
     * @template T of Node
     * @param T $node
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node) : Node
    {
        $new = Visitor::visit($node, [
            NodeKind::FIELD => function (Node $node) : ?InlineFragmentNode {
                Assert::isInstanceOf($node, FieldNode::class);

                foreach ($node->directives as $directive) {
                    if ($directive->name->value !== 'hook') {
                        continue;
                    }

                    $hookName = $this->readHookName($directive);

                    Assert::keyExists($this->hooks, $hookName, sprintf(
                        'Hook "%s" used in a @hook directive is not registered via Config::withHook().',
                        $hookName,
                    ));

                    $fragment = $this->hooks[$hookName]->requiresFragment;

                    return new InlineFragmentNode([
                        'typeCondition' => $fragment->typeCondition->cloneDeep(),
                        'directives' => new NodeList([]),
                        'selectionSet' => $fragment->selectionSet->cloneDeep(),
                    ]);
                }

                return null;
            },
        ]);

        Assert::isInstanceOf($new, Node::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function readHookName(DirectiveNode $directive) : string
    {
        foreach ($directive->arguments as $argument) {
            if ($argument->name->value === 'name' && $argument->value instanceof StringValueNode) {
                return $argument->value->value;
            }
        }

        throw new InvalidArgumentException('A @hook directive is missing its "name" argument.');
    }
}
