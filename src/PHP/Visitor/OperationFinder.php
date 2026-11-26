<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use Override;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Planner\SchemaSelector;

final class OperationFinder extends NodeVisitorAbstract
{
    /**
     * @var array<string, array<string, array<string, string>>>
     */
    public private(set) array $operations = [];
    private ?string $className = null;

    /**
     * @param array<string, String_> $constants
     */
    public function __construct(
        private array $constants,
        private SchemaSelector $schemaSelector = new SchemaSelector(),
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    public function enterNode(Node $node): null
    {
        if ($node instanceof Node\Stmt\Class_ && $node->name !== null) {
            $this->className = $node->namespacedName?->toString();

            return null;
        }

        if (!$node instanceof Node\Stmt\ClassMethod) {
            return null;
        }

        if ($this->className === null) {
            return null;
        }

        foreach ($node->params as $param) {
            if (!$param->var instanceof Node\Expr\Variable) {
                continue;
            }

            $paramName = $param->var->name;

            if (!is_string($paramName)) {
                continue;
            }

            foreach ($param->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attr) {
                    if ($attr->name->toString() !== GeneratedGraphQLClient::class) {
                        continue;
                    }

                    $value = AttributeArguments::find($attr, 'operation', 0);

                    if ($value === null) {
                        continue;
                    }

                    $location = sprintf('%s::%s::$%s', $this->className, $node->name->toString(), $paramName);
                    $schema = AttributeArguments::string($attr, 'schema', 1, $this->constants, $location);

                    if (!$this->schemaSelector->selects($schema, $location)) {
                        continue;
                    }

                    if ($value instanceof Node\Expr\ClassConstFetch && $value->name instanceof Node\Identifier) {
                        $value = $this->constants[$value->name->toString()];
                    }

                    if (!$value instanceof String_) {
                        throw new InvalidArgumentException('Invalid argument type');
                    }

                    $this->operations[$this->className][$node->name->toString()][$paramName] = $value->value;

                    continue 3;
                }
            }
        }

        return null;
    }

    #[Override]
    public function leaveNode(Node $node): null
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return null;
        }

        $this->className = null;

        return null;
    }
}
