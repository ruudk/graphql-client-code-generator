<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use Ruudk\GraphQLCodeGenerator\Config\ConfigException;

/**
 * The hooks of a deprecated generated property read the property itself, which the
 * phpstan/phpstan-deprecation-rules package reports as a read of a deprecated property.
 * Ignore those reads inside generated classes, so only reads in user code are reported.
 *
 * An inline `@phpstan-ignore` can't do this: without the deprecation rules nothing is
 * reported, and PHPStan then reports the unmatched ignore instead.
 */
final readonly class DeprecatedPropertyReadIgnoreExtension implements IgnoreErrorExtension
{
    private GeneratedNamespaces $namespaces;

    /**
     * @param list<string> $configs
     * @throws ConfigException
     */
    public function __construct(array $configs)
    {
        $this->namespaces = GeneratedNamespaces::fromConfigs($configs);
    }

    #[Override]
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'property.deprecated') {
            return false;
        }

        if (!$node instanceof PropertyFetch || !$node->var instanceof Variable || $node->var->name !== 'this') {
            return false;
        }

        $classReflection = $scope->getClassReflection();

        return $classReflection !== null && $this->namespaces->contains($classReflection->getName());
    }
}
