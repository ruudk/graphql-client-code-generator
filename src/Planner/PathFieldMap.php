<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Maps field paths to their types (e.g., "query.user.name" => string)
 */
final class PathFieldMap
{
    /**
     * @var array<string, SymfonyType>
     */
    private array $paths = [];

    public function add(string $path, SymfonyType $type) : self
    {
        $this->paths[$path] = $type;

        return $this;
    }

    public function addWithPrefix(string $prefix, string $field, SymfonyType $type) : self
    {
        return $this->add($prefix . '.' . $field, $type);
    }

    /**
     * @return array<string, SymfonyType>
     */
    public function all() : array
    {
        return $this->paths;
    }

    public function get(string $path) : ?SymfonyType
    {
        return $this->paths[$path] ?? null;
    }

    public function merge(PathFieldMap $other) : self
    {
        foreach ($other->paths as $path => $type) {
            $this->paths[$path] = $type;
        }

        return $this;
    }
}
