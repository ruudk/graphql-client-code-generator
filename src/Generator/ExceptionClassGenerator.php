<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Exception;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\ExceptionClassPlan;

final class ExceptionClassGenerator extends AbstractGenerator
{
    public function generate(ExceptionClassPlan $plan) : string
    {
        $operationType = $plan->operationType;
        $operationName = $plan->operationName;
        $className = $plan->exceptionClassName;
        $generator = new CodeGenerator($this->fullyQualified($operationType, $operationName));

        return $generator->dumpFile(function () use ($className, $generator) {
            yield $this->dumpHeader();

            yield '';
            yield sprintf('final class %s extends %s', $className, $generator->import(Exception::class));
            yield '{';
            yield $generator->indent(function () use ($generator, $className) {
                yield 'public function __construct(';
                yield $generator->indent('public readonly Data $data,');
                yield ') {';
                yield $generator->indent(function () use ($generator, $className) {
                    yield 'parent::__construct(sprintf(';
                    yield $generator->indent([
                        sprintf("'%s failed%%s',", $className),
                        "\$data->errors !== [] ? sprintf(': %s', \$data->errors[0]->message) : '',",
                    ]);
                    yield '));';
                });
                yield '}';
            });
            yield '}';
        });
    }
}
