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

    public function has(string $key) : bool
    {
        return isset($this->shape[$key]);
    }

    /**
     * Get the type for a specific key
     */
    public function getType(string $key) : ?SymfonyType
    {
        if ( ! isset($this->shape[$key])) {
            return null;
        }

        $value = $this->shape[$key];

        return is_array($value) ? $value['type'] : $value;
    }

    /**
     * Replace the type for an existing key
     */
    public function replaceType(string $key, SymfonyType $type) : self
    {
        if ( ! isset($this->shape[$key])) {
            return $this;
        }

        $value = $this->shape[$key];

        if (is_array($value)) {
            $value['type'] = $type;
            $this->shape[$key] = $value;
        } else {
            $this->shape[$key] = $type;
        }

        return $this;
    }

    /**
     * Merge another payload shape into this one
     * @param bool $asOptional If true, all merged fields become optional
     */
    public function merge(PayloadShape $other, bool $asOptional = false) : self
    {
        foreach ($other->shape as $key => $value) {
            $type = is_array($value) ? $value['type'] : $value;
            $isOptional = $asOptional || (is_array($value) && $value['optional']);

            if ($this->has($key)) {
                // Field exists - need to merge if both are array shapes
                $existingType = $this->getType($key);

                // Merge array shapes (nested objects)
                if ($existingType instanceof SymfonyType\ArrayShapeType && $type instanceof SymfonyType\ArrayShapeType) {
                    $mergedElements = array_merge(
                        $existingType->getShape(),
                        $type->getShape(),
                    );
                    $this->replaceType($key, SymfonyType::arrayShape($mergedElements));

                    continue;
                }

                // Merge lists of array shapes
                if ($existingType instanceof SymfonyType\CollectionType && $type instanceof SymfonyType\CollectionType) {
                    $existingInner = $existingType->getCollectionValueType();
                    $newInner = $type->getCollectionValueType();

                    if ($existingInner instanceof SymfonyType\ArrayShapeType && $newInner instanceof SymfonyType\ArrayShapeType) {
                        $mergedElements = array_merge(
                            $existingInner->getShape(),
                            $newInner->getShape(),
                        );
                        $this->replaceType($key, SymfonyType::list(SymfonyType::arrayShape($mergedElements)));
                    }
                }

                // Otherwise keep existing type
                continue;
            }

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
