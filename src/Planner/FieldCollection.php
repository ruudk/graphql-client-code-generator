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

    public function add(string $name, SymfonyType $type) : self
    {
        $this->fields[$name] = $type;

        return $this;
    }

    public function merge(FieldCollection $other) : self
    {
        foreach ($other->fields as $name => $type) {
            $this->fields[$name] = $type;
        }

        return $this;
    }

    public function toArrayShape() : SymfonyType
    {
        return SymfonyType::arrayShape($this->fields);
    }

    public function clone() : self
    {
        $clone = new self();
        $clone->fields = $this->fields;

        return $clone;
    }

    /**
     * @return array<string, SymfonyType>
     */
    public function getFields() : array
    {
        return $this->fields;
    }
}
