<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Collection of fields with their types
 */
final class FieldCollection
{
    /**
     * @var array<string, SymfonyType>
     */
    private array $fields = [];

    public function add(string $name, SymfonyType $type): self
    {
        $this->fields[$name] = $type;

        return $this;
    }

    public function get(string $name): SymfonyType
    {
        return $this->fields[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * @return array<string, SymfonyType>
     */
    public function all(): array
    {
        return $this->fields;
    }

    public function toArrayShape(): SymfonyType
    {
        return SymfonyType::arrayShape($this->fields, sealed: false);
    }
}
