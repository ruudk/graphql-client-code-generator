<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Result from planning a selection set
 */
final readonly class SelectionSetPlanResult
{
    /**
     * @param array<string, SymfonyType> $fields2
     * @param array<string, SymfonyType> $fragmentPayloadShapes
     * @param array<string, Type&NamedType> $fragmentTypes
     * @param array<string, list<string>> $inlineFragmentRequiredFields
     */
    public function __construct(
        public SymfonyType $fields,
        public array $fields2,
        public SymfonyType $payloadShape,
        public SymfonyType $type,
        public PlannerResult $plannerResult,
        public array $fragmentPayloadShapes,
        public array $fragmentTypes,
        public array $inlineFragmentRequiredFields,
    ) {}
}
