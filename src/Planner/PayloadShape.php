<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
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

    /**
     * Per-variant shapes for polymorphic selections (inline fragments on
     * interface/union parents). Keyed by concrete type name; the stored
     * `PayloadShape` only holds fields selected inside that variant's
     * fragment, not the common fields from the parent selection set.
     *
     * @var array<string, PayloadShape>
     */
    private array $variants = [];

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

    public function addVariant(string $typeName, PayloadShape $variantShape) : self
    {
        // Always store an independent copy. The same source shape is reused
        // when distributing an abstract fragment (e.g. `... on Person`) to
        // every concrete implementor, and a shared reference would let one
        // implementor's later additions leak into the others.
        if ( ! isset($this->variants[$typeName])) {
            $this->variants[$typeName] = new PayloadShape();
        }

        $this->variants[$typeName]->merge($variantShape);

        // If the source shape itself carried a nested variant whose name
        // matches the destination — e.g. distributing an `... on Person` shape
        // (which has a nested `... on Employee` variant for `Developer`) to
        // the `Developer` implementor — collapse those nested fields into
        // this destination directly. Without this, fields from nested inline
        // fragments would only live inside an unreachable second-level
        // variant and never surface in the emitted arm.
        if (isset($variantShape->variants[$typeName])) {
            $this->variants[$typeName]->merge($variantShape->variants[$typeName]);
        }

        return $this;
    }

    public function has(string $key) : bool
    {
        return isset($this->shape[$key]);
    }

    public function hasVariants() : bool
    {
        return $this->variants !== [];
    }

    public function hasVariant(string $typeName) : bool
    {
        return isset($this->variants[$typeName]);
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
                    $this->replaceType($key, SymfonyType::arrayShape($mergedElements, sealed: false));

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
                        $this->replaceType($key, SymfonyType::list(SymfonyType::arrayShape($mergedElements, sealed: false)));
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

        foreach ($other->variants as $typeName => $variant) {
            $this->addVariant($typeName, $asOptional ? $variant->withFieldsOptional() : $variant);
        }

        return $this;
    }

    /**
     * Return a copy with every direct field demoted to optional. Used when a
     * conditional fragment (`@include`/`@skip`) contributes variant shapes —
     * the variant arm exists, but each of its fields may or may not show up.
     */
    private function withFieldsOptional() : self
    {
        $clone = new self();

        foreach ($this->shape as $key => $value) {
            $type = is_array($value) ? $value['type'] : $value;
            $clone->addOptional($key, $type);
        }

        foreach ($this->variants as $typeName => $variant) {
            $clone->addVariant($typeName, $variant->withFieldsOptional());
        }

        return $clone;
    }

    public function toArrayShape() : SymfonyType
    {
        if ($this->variants === []) {
            return SymfonyType::arrayShape($this->shape, sealed: false);
        }

        $arms = [];

        foreach ($this->variants as $typeName => $variant) {
            $combined = $this->shape;

            foreach ($variant->shape as $key => $value) {
                $combined[$key] = $value;
            }

            $combined['__typename'] = new StringLiteralType($typeName);

            // Arms must be sealed: PHPStan's narrowing on `__typename` literal
            // only preserves required fields when each arm is sealed. Unsealed
            // arms collapse to common-only after narrowing, dropping the
            // variant-specific fields the variant subclass constructor needs.
            $arms[] = SymfonyType::arrayShape($combined, sealed: true);
        }

        return count($arms) === 1 ? $arms[0] : SymfonyType::union(...$arms);
    }
}
