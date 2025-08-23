<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Represents the shape of a GraphQL payload
 */
final class PayloadShape
{
    /**
     * @var array<string, SymfonyType|array{type: SymfonyType, optional: bool}>
     */
    private array $shape = [];

    public function addRequired(string $key, SymfonyType $type) : self
    {
        $this->shape[$key] = $type;

        return $this;
    }

    public function addOptional(string $key, SymfonyType $type) : self
    {
        $this->shape[$key] = [
            'type' => $type,
            'optional' => true,
        ];

        return $this;
    }

    public function add(string $key, SymfonyType $type, bool $optional = false) : self
    {
        if ($optional) {
            return $this->addOptional($key, $type);
        }

        return $this->addRequired($key, $type);
    }

    public function has(string $key) : bool
    {
        return isset($this->shape[$key]);
    }

    /**
     * Merge another payload shape into this one
     * @param bool $asOptional If true, all merged fields become optional
     */
    public function merge(PayloadShape $other, bool $asOptional = false) : self
    {
        foreach ($other->shape as $key => $value) {
            if ($this->has($key)) {
                continue;
            }

            $isOptional = $asOptional || (is_array($value) && $value['optional']);
            $type = is_array($value) ? $value['type'] : $value;

            if ($isOptional) {
                $this->addOptional($key, $type);
            } else {
                $this->addRequired($key, $type);
            }
        }

        return $this;
    }

    public function toArrayShape() : SymfonyType
    {
        return SymfonyType::arrayShape($this->shape);
    }

    public function clone() : self
    {
        $clone = new self();
        $clone->shape = $this->shape;

        return $clone;
    }
}
