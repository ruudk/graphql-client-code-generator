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
        $generator = new CodeGenerator($plan->namespace);

        return $generator->dumpFile(function () use ($plan, $generator) {
            yield $this->dumpHeader();

            yield '';
            yield sprintf('final class %s extends %s', $plan->className, $generator->import(Exception::class));
            yield '{';
            yield $generator->indent(function () use ($generator, $plan) {
                yield 'public function __construct(';
                yield $generator->indent('public readonly Data $data,');
                yield ') {';
                yield $generator->indent(function () use ($generator, $plan) {
                    yield 'parent::__construct(sprintf(';
                    yield $generator->indent([
                        sprintf("'%s failed%%s',", $plan->className),
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
