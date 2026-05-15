<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Overfetching;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use Throwable;

/**
 * Reads the fetched-data shape of a generated class straight from the
 * generated PHP file: its public properties, whether each is a real fetched
 * field (its getter reads `$this->data`), whether it is tagged `@api`, and the
 * generated class it resolves to.
 */
final class ShapeReader
{
    private readonly Parser $parser;
    private readonly NodeFinder $finder;

    /**
     * @var array<string, null|Shape>
     */
    private array $cache = [];

    public function __construct()
    {
        $this->parser = new ParserFactory()->createForNewestSupportedVersion();
        $this->finder = new NodeFinder();
    }

    public function read(string $fqcn) : ?Shape
    {
        $fqcn = ltrim($fqcn, '\\');

        if (array_key_exists($fqcn, $this->cache)) {
            return $this->cache[$fqcn];
        }

        return $this->cache[$fqcn] = $this->doRead($fqcn);
    }

    private function doRead(string $fqcn) : ?Shape
    {
        if ( ! class_exists($fqcn)) {
            return null;
        }

        try {
            $fileName = new ReflectionClass($fqcn)->getFileName();
        } catch (Throwable) {
            return null;
        }

        if ($fileName === false || ! is_file($fileName)) {
            return null;
        }

        $code = @file_get_contents($fileName);

        if ($code === false) {
            return null;
        }

        try {
            $stmts = $this->parser->parse($code);
        } catch (Throwable) {
            return null;
        }

        if ($stmts === null) {
            return null;
        }

        $stmts = new NodeTraverser(new NameResolver())->traverse($stmts);

        $useMap = $this->collectUseMap($stmts);

        $class = $this->finder->findFirstInstanceOf($stmts, Class_::class);

        if ( ! $class instanceof Class_) {
            return null;
        }

        $namespace = $this->namespaceOf($fqcn);
        $source = $this->readGeneratedSource($class);

        $properties = [];

        foreach ($class->getProperties() as $property) {
            if ( ! $property->isPublic() || $property->isStatic()) {
                continue;
            }

            foreach ($property->props as $prop) {
                $name = $prop->name->toString();

                [$targetFqcn, $isList] = $this->resolveType($property, $useMap, $namespace);

                $properties[$name] = new ShapeProperty(
                    $name,
                    $this->hasApiTag($property),
                    $this->getterReadsData($property),
                    $targetFqcn,
                    $isList,
                );
            }
        }

        return new Shape($fqcn, $source, $properties);
    }

    /**
     * @param array<Node> $stmts
     * @return array<string, string>
     */
    private function collectUseMap(array $stmts) : array
    {
        $map = [];

        foreach ($this->finder->findInstanceOf($stmts, Use_::class) as $use) {
            if ($use->type !== Use_::TYPE_NORMAL) {
                continue;
            }

            foreach ($use->uses as $useUse) {
                $fqcn = $useUse->name->toString();
                $alias = $useUse->alias?->toString() ?? $useUse->name->getLast();
                $map[$alias] = $fqcn;
            }
        }

        return $map;
    }

    private function readGeneratedSource(Class_ $class) : ?string
    {
        foreach ($class->attrGroups as $group) {
            foreach ($group->attrs as $attr) {
                if ($attr->name->getLast() !== 'Generated') {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    $isSource = $arg->name === null || $arg->name->toString() === 'source';

                    if ( ! $isSource) {
                        continue;
                    }

                    $value = $arg->value;

                    if ($value instanceof Node\Scalar\String_) {
                        return $value->value;
                    }
                }
            }
        }

        return null;
    }

    private function hasApiTag(Property $property) : bool
    {
        $doc = $property->getDocComment()?->getText();

        if ($doc === null) {
            return false;
        }

        return preg_match('/^\s*\*?\s*@api\b/m', $doc) === 1;
    }

    private function getterReadsData(Property $property) : bool
    {
        foreach ($property->hooks as $hook) {
            if ($hook->name->toString() !== 'get') {
                continue;
            }

            $body = $hook->body;

            if ($body === null) {
                continue;
            }

            $nodes = $body instanceof Node ? [$body] : $body;

            $found = $this->finder->findFirst($nodes, static function (Node $node) : bool {
                return $node instanceof PropertyFetch
                    && $node->var instanceof Variable
                    && $node->var->name === 'this'
                    && $node->name instanceof Identifier
                    && $node->name->toString() === 'data';
            });

            if ($found !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $useMap
     * @return array{0: ?string, 1: bool}
     */
    private function resolveType(Property $property, array $useMap, string $namespace) : array
    {
        $type = $property->type;

        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $member) {
                if ($member instanceof Name) {
                    return [$member->toString(), false];
                }
            }

            return [null, false];
        }

        if ($type instanceof Name) {
            return [$type->toString(), false];
        }

        if ($type instanceof Identifier && in_array($type->toString(), ['array', 'iterable'], true)) {
            $element = $this->elementFromVar($property, $useMap, $namespace);

            return [$element, $element !== null];
        }

        return [null, false];
    }

    /**
     * @param array<string, string> $useMap
     */
    private function elementFromVar(Property $property, array $useMap, string $namespace) : ?string
    {
        $doc = $property->getDocComment()?->getText();

        if ($doc === null) {
            return null;
        }

        if (preg_match('/@var\s+(?:list|array|iterable|non-empty-list)\s*<\s*(?:[^,>]+,\s*)?\\\\?([A-Za-z_][\w\\\\]*)\s*>/', $doc, $m) === 1) {
            return $this->resolveClassName($m[1], $useMap, $namespace);
        }

        if (preg_match('/@var\s+\\\\?([A-Za-z_][\w\\\\]*)\s*\[\s*\]/', $doc, $m) === 1) {
            return $this->resolveClassName($m[1], $useMap, $namespace);
        }

        return null;
    }

    /**
     * @param array<string, string> $useMap
     */
    private function resolveClassName(string $name, array $useMap, string $namespace) : ?string
    {
        if (str_contains($name, '\\')) {
            return ltrim($name, '\\');
        }

        if (isset($useMap[$name])) {
            return $useMap[$name];
        }

        if (in_array($name, ['string', 'int', 'float', 'bool', 'mixed', 'array', 'object', 'null'], true)) {
            return null;
        }

        return $namespace === '' ? $name : $namespace . '\\' . $name;
    }

    private function namespaceOf(string $fqcn) : string
    {
        $position = strrpos($fqcn, '\\');

        return $position === false ? '' : substr($fqcn, 0, $position);
    }
}
