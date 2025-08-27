<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Executor;

use JsonException;
use LogicException;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Generator\DataClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\EnumTypeGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\ErrorClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\ExceptionClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\InputTypeGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\NodeNotFoundExceptionGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\OperationClassGenerator;
use Ruudk\GraphQLCodeGenerator\Planner\OperationPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\EnumClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\InputClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\NodeNotFoundExceptionPlan;
use Ruudk\GraphQLCodeGenerator\Planner\PlannerResult;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\BackedEnumTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\CollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\IndexByCollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\NullableTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ObjectTypeInitializer;

final class PlanExecutor
{
    private readonly DataClassGenerator $dataClassGenerator;
    private readonly EnumTypeGenerator $enumTypeGenerator;
    private readonly OperationClassGenerator $operationClassGenerator;
    private readonly ErrorClassGenerator $errorClassGenerator;
    private readonly ExceptionClassGenerator $exceptionClassGenerator;
    private readonly InputTypeGenerator $inputTypeGenerator;
    private readonly NodeNotFoundExceptionGenerator $nodeNotFoundExceptionGenerator;

    public function __construct(
        Config $config,
    ) {
        // Initialize type initializer
        $typeInitializer = new DelegatingTypeInitializer(
            new NullableTypeInitializer(),
            new IndexByCollectionTypeInitializer(),
            new CollectionTypeInitializer(),
            new BackedEnumTypeInitializer($config->addUnknownCaseToEnums, $config->namespace),
            new ObjectTypeInitializer(),
            ...$config->typeInitializers,
        );

        // Initialize all generators
        $this->dataClassGenerator = new DataClassGenerator($config, $typeInitializer);
        $this->enumTypeGenerator = new EnumTypeGenerator($config);
        $this->operationClassGenerator = new OperationClassGenerator($config);
        $this->errorClassGenerator = new ErrorClassGenerator($config);
        $this->exceptionClassGenerator = new ExceptionClassGenerator($config);
        $this->inputTypeGenerator = new InputTypeGenerator($config);
        $this->nodeNotFoundExceptionGenerator = new NodeNotFoundExceptionGenerator($config);
    }

    /**
     * @throws LogicException
     * @throws JsonException
     * @return array<string, string>
     */
    public function execute(PlannerResult $plan) : array
    {
        $files = [];

        foreach ($plan->classes as $relativePath => $class) {
            $files[$relativePath] = $this->generateClass($class);
        }

        foreach ($plan->operations as $operation) {
            $files = array_merge($files, $this->generateOperation($operation));
        }

        return $files;
    }

    /**
     * @throws LogicException
     */
    private function generateClass(object $class) : string
    {
        return match ($class::class) {
            DataClassPlan::class => $this->dataClassGenerator->generate($class),

            EnumClassPlan::class => $this->enumTypeGenerator->generate($class),

            InputClassPlan::class => $this->inputTypeGenerator->generate($class),

            NodeNotFoundExceptionPlan::class => $this->nodeNotFoundExceptionGenerator->generate(),

            default => throw new LogicException('Unknown class type: ' . $class::class),
        };
    }

    /**
     * @throws JsonException
     * @return array<string, string>
     */
    private function generateOperation(OperationPlan $operation) : array
    {
        $files = [];

        // Generate operation class
        $files[$operation->operationClass->relativePath] = $this->operationClassGenerator->generate(
            $operation->operationClass,
        );

        // Generate error class
        $files[$operation->errorClass->relativePath] = $this->errorClassGenerator->generate(
            $operation->errorClass,
        );

        // Generate exception class only if it exists
        if ($operation->exceptionClass !== null) {
            $files[$operation->exceptionClass->relativePath] = $this->exceptionClassGenerator->generate(
                $operation->exceptionClass,
            );
        }

        return $files;
    }
}
