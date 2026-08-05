<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Overfetching;

use Ruudk\GraphQLCodeGenerator\Twig\GraphQLExtension;
use Ruudk\GraphQLCodeGenerator\Twig\GraphQLNode;
use Throwable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ForNode;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Node\TypesNode;
use Twig\Source;

/**
 * Detects GraphQL over-fetching in a Twig template: fields fetched by the
 * `{% graphql %}` fragment (visible as properties on the generated class the
 * `{% types %}` tag maps a variable to) that the template never reads back.
 *
 * The generated class is the source of truth for what is fetched; the template
 * AST is walked - threading a {@see Scope} so `{% for %}`/`{% set %}` aliases
 * resolve - to mark which of those fields are actually used.
 */
final class OverfetchingAnalyzer
{
    /**
     * @var array<string, true>
     */
    private array $used = [];

    public function __construct(
        private readonly ShapeReader $reader = new ShapeReader(),
    ) {}

    /**
     * @return list<string>
     */
    public function analyze(string $source, string $fileName) : array
    {
        $this->used = [];

        try {
            $environment = new Environment(new ArrayLoader(), [
                'cache' => false,
                'autoescape' => false,
                'optimizations' => 0,
                'strict_variables' => false,
            ]);
            $environment->addExtension(new GraphQLExtension());

            $module = $environment->parse(
                $environment->tokenize(new Source($source, $fileName)),
            );
        } catch (Throwable) {
            return [];
        }

        $typesNode = $this->findTypesNode($module);

        if ($typesNode === null) {
            return [];
        }

        /**
         * @var array<string, array{type: string, optional: bool}> $mapping
         */
        $mapping = $typesNode->getAttribute('mapping');

        $scope = new Scope();
        $roots = [];

        foreach ($mapping as $name => $definition) {
            $fqcn = ltrim($definition['type'], '\\');

            if ($this->reader->read($fqcn) === null) {
                continue;
            }

            $scope = $scope->with($name, new Binding($fqcn));
            $roots[$name] = $fqcn;
        }

        if ($roots === []) {
            return [];
        }

        $this->walk($module, $scope);

        $messages = [];

        foreach ($roots as $name => $fqcn) {
            $this->report($fqcn, $name, [], $messages);
        }

        return $messages;
    }

    private function findTypesNode(Node $node) : ?TypesNode
    {
        if ($node instanceof TypesNode) {
            return $node;
        }

        foreach ($node as $child) {
            $found = $this->findTypesNode($child);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Walks the tree in order, returning the (possibly extended) scope so a
     * `{% set %}` is visible to its following siblings.
     */
    private function walk(Node $node, Scope $scope) : Scope
    {
        if ($node instanceof TypesNode || $node instanceof GraphQLNode) {
            return $scope;
        }

        if ($node instanceof NameExpression) {
            // A bare variable: the whole object escapes (printed, passed to a
            // function/macro/include). We cannot trace further, so everything
            // below it counts as used.
            $binding = $scope->get($this->nameOf($node));

            if ($binding->fqcn !== null) {
                $this->markAllUsed($binding->fqcn, []);
            }

            return $scope;
        }

        if ($node instanceof GetAttrExpression) {
            $this->resolveChain($node, $scope);

            return $scope;
        }

        if ($node instanceof ForNode) {
            $sequence = $this->typeOf($node->getNode('seq'), $scope);

            $body = $scope
                ->with($this->nameOf($node->getNode('value_target')), $sequence->element())
                ->with($this->nameOf($node->getNode('key_target')), Binding::unknown())
                ->with('loop', Binding::unknown());

            $this->walk($node->getNode('body'), $body);

            if ($node->hasNode('else')) {
                $this->walk($node->getNode('else'), $scope);
            }

            return $scope;
        }

        if ($node instanceof SetNode && ! $node->getAttribute('capture')) {
            $names = $node->getNode('names');
            $values = $node->getNode('values');

            $nameList = array_values(iterator_to_array($names));
            $valueList = array_values(iterator_to_array($values));

            if (count($nameList) === 1 && count($valueList) === 1) {
                return $scope->with(
                    $this->nameOf($nameList[0]),
                    $this->typeOf($valueList[0], $scope),
                );
            }

            foreach ($values as $value) {
                $this->walk($value, $scope);
            }

            return $scope;
        }

        $childScope = $scope;

        foreach ($node as $child) {
            $childScope = $this->walk($child, $childScope);
        }

        return $scope;
    }

    private function nameOf(Node $node) : string
    {
        $name = $node->hasAttribute('name') ? $node->getAttribute('name') : null;

        return is_string($name) ? $name : '';
    }

    /**
     * Resolves the type an expression evaluates to, recording any attribute
     * accesses it performs as used along the way.
     */
    private function typeOf(Node $node, Scope $scope) : Binding
    {
        if ($node instanceof NameExpression) {
            return $scope->get($this->nameOf($node));
        }

        if ($node instanceof GetAttrExpression) {
            return $this->resolveChain($node, $scope);
        }

        $this->walk($node, $scope);

        return Binding::unknown();
    }

    private function resolveChain(GetAttrExpression $node, Scope $scope) : Binding
    {
        $steps = [];
        $current = $node;

        while ($current instanceof GetAttrExpression) {
            $attribute = $current->getNode('attribute');

            $value = $attribute instanceof ConstantExpression
                ? $attribute->getAttribute('value')
                : null;

            $steps[] = is_string($value) ? $value : null;

            $current = $current->getNode('node');
        }

        $steps = array_reverse($steps);

        $binding = $this->typeOf($current, $scope);
        $fqcn = $binding->fqcn;

        foreach ($steps as $step) {
            if ($fqcn === null) {
                return Binding::unknown();
            }

            if ($step === null) {
                // Dynamic access (`foo[bar]`): we cannot tell which field is
                // read, so conservatively treat the whole subtree as used.
                $this->markAllUsed($fqcn, []);

                return Binding::unknown();
            }

            $shape = $this->reader->read($fqcn);

            if ($shape === null || ! isset($shape->properties[$step])) {
                return Binding::unknown();
            }

            $property = $shape->properties[$step];
            $this->used[$fqcn . "\0" . $step] = true;

            $fqcn = $property->targetFqcn;

            if ($step === $steps[array_key_last($steps)]) {
                return new Binding($fqcn, $property->list);
            }
        }

        return new Binding($fqcn);
    }

    /**
     * @param list<string> $visited
     */
    private function markAllUsed(string $fqcn, array $visited) : void
    {
        if (in_array($fqcn, $visited, true)) {
            return;
        }

        $shape = $this->reader->read($fqcn);

        if ($shape === null) {
            return;
        }

        $visited[] = $fqcn;

        foreach ($shape->properties as $property) {
            $this->used[$fqcn . "\0" . $property->name] = true;

            if ($property->targetFqcn === null) {
                continue;
            }

            $target = $this->reader->read($property->targetFqcn);

            if ($target !== null && $target->source === $shape->source) {
                $this->markAllUsed($property->targetFqcn, $visited);
            }
        }
    }

    /**
     * @param list<string> $visited
     * @param list<string> $messages
     */
    private function report(string $fqcn, string $path, array $visited, array &$messages) : void
    {
        if (in_array($fqcn, $visited, true)) {
            return;
        }

        $shape = $this->reader->read($fqcn);

        if ($shape === null) {
            return;
        }

        $visited[] = $fqcn;

        foreach ($shape->properties as $property) {
            if ( ! $property->fetchedField || $property->api) {
                continue;
            }

            $childPath = $path . '.' . $property->name;

            if ( ! isset($this->used[$fqcn . "\0" . $property->name])) {
                $messages[] = sprintf(
                    '`%s` is fetched in the GraphQL operation but never used in the template (overfetching).',
                    $childPath,
                );

                continue;
            }

            if ($property->targetFqcn === null) {
                continue;
            }

            $target = $this->reader->read($property->targetFqcn);

            // Only recurse into nested object selections that belong to the
            // same template. A fragment spread points at a different template
            // and is validated when that template is linted.
            if ($target !== null && $target->source === $shape->source) {
                $this->report($property->targetFqcn, $childPath, $visited, $messages);
            }
        }
    }
}
