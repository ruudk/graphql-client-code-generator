<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Contains all planned classes and their dependencies
 */
final class PlannerResult
{
    /**
     * @var array<string, object> Map of relative path to plan object
     */
    public private(set) array $classes = [];

    /**
     * @var array<string, OperationPlan>
     */
    public private(set) array $operations = [];

    /**
     * @var array<string, SymfonyType>
     */
    public private(set) array $discoveredEnumTypes = [];

    /**
     * @var array<string, SymfonyType>
     */
    public private(set) array $discoveredInputObjectTypes = [];

    /**
     * @param object $class A plan object with a relativePath property
     */
    public function addClass(object $class) : void
    {
        /** @var object{relativePath: string} $class */
        $this->classes[$class->relativePath] = $class;
    }

    public function addOperation(OperationPlan $operation) : void
    {
        $this->operations[$operation->operationName] = $operation;
    }

    /**
     * @param array<string, SymfonyType> $enumTypes
     */
    public function setDiscoveredEnumTypes(array $enumTypes) : void
    {
        $this->discoveredEnumTypes = $enumTypes;
    }

    /**
     * @param array<string, SymfonyType> $inputObjectTypes
     */
    public function setDiscoveredInputObjectTypes(array $inputObjectTypes) : void
    {
        $this->discoveredInputObjectTypes = $inputObjectTypes;
    }
}
