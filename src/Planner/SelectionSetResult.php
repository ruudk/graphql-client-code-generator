<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Result of planning a selection set
 */
final readonly class SelectionSetResult
{
    public function __construct(
        public FieldCollection $fields,
        public PathFieldMap $pathFields,
        public PayloadShape $payloadShape,
        public SymfonyType $resultType,
        public ?SymfonyType $wrappedFields = null,
        public ?SymfonyType $wrappedPayloadShape = null,
    ) {}

    public function getFieldsType() : SymfonyType
    {
        return $this->wrappedFields ?? $this->fields->toArrayShape();
    }

    public function getPayloadShapeType() : SymfonyType
    {
        return $this->wrappedPayloadShape ?? $this->payloadShape->toArrayShape();
    }
}
