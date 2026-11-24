<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use Override;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLFragment;

final class FragmentFinder extends NodeVisitorAbstract
{
    /**
     * @var array<string, array<string, array<string, string>>>
     */
    public private(set) array $fragments = [];

    /**
     * @var array<string, ?string>
     */
    public private(set) array $skipped = [];
    private ?string $className = null;

    /**
     * @param array<string, String_> $constants
     */
    public function __construct(
        private array $constants,
        private ?string $scope = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    public function enterNode(Node $node) : null
    {
        if ($node instanceof Node\Stmt\Class_ && $node->name !== null) {
            $this->className = $node->namespacedName?->toString();

            return null;
        }

        if ( ! $node instanceof Node\Stmt\ClassMethod) {
            return null;
        }

        if ($this->className === null) {
            return null;
        }

        foreach ($node->params as $param) {
            if ( ! $param->var instanceof Node\Expr\Variable) {
                continue;
            }

            if ( ! is_string($param->var->name)) {
                continue;
            }

            foreach ($param->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attr) {
                    if ($attr->name->toString() !== GeneratedGraphQLFragment::class) {
                        continue;
                    }

                    $location = sprintf('%s::%s::$%s', $this->className, $node->name->toString(), $param->var->name);
                    $scope = AttributeArguments::scope($attr, $location);

                    if ($scope !== $this->scope) {
                        $this->skipped[$location] = $scope;

                        continue;
                    }

                    if ($node->name->toString() === '__construct') {
                        throw new InvalidArgumentException(sprintf(
                            'The #[GeneratedGraphQLFragment] attribute cannot be used on constructor parameters. Found on "%s::__construct::$%s". Use it on a non-constructor method parameter instead.',
                            $this->className,
                            $param->var->name,
                        ));
                    }

                    $value = AttributeArguments::find($attr, 'fragment', 0);

                    if ($value === null) {
                        continue;
                    }

                    if ($value instanceof Node\Expr\ClassConstFetch && $value->name instanceof Node\Identifier) {
                        $value = $this->constants[$value->name->toString()];
                    }

                    if ( ! $value instanceof String_) {
                        throw new InvalidArgumentException('Invalid argument type');
                    }

                    $this->fragments[$this->className][$node->name->toString()][$param->var->name] = $value->value;

                    continue 3;
                }
            }
        }

        return null;
    }

    #[Override]
    public function leaveNode(Node $node) : null
    {
        if ( ! $node instanceof Node\Stmt\Class_) {
            return null;
        }

        $this->className = null;

        return null;
    }
}
