<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\ErrorClassPlan;
use Symfony\Component\TypeInfo\Type;

final class ErrorClassGenerator extends AbstractGenerator
{
    public function generate(ErrorClassPlan $plan) : string
    {
        $operationType = $plan->operationType;
        $operationName = $plan->operationName;
        $generator = new CodeGenerator($this->fullyQualified($operationType, $operationName));

        return $generator->dumpFile(function () use ($generator) {
            yield $this->dumpHeader();

            yield '';

            if ($this->config->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield 'final readonly class Error';
            yield '{';
            yield $generator->indent(function () use ($generator) {
                yield 'public string $message;';
                yield 'public string $code;';

                yield '';
                yield from $generator->docComment(sprintf('@param %s $error', $this->dumpPHPDocType(Type::arrayShape([
                    'message' => Type::string(),
                    'code' => Type::string(),
                    'debugMessage' => [
                        'type' => Type::string(),
                        'optional' => true,
                    ],
                ]), $generator->import(...))));
                yield 'public function __construct(array $error)';
                yield '{';
                yield $generator->indent(function () {
                    yield "\$this->message = \$error['debugMessage'] ?? \$error['message'];";
                    yield "\$this->code = \$error['code'];";
                });
                yield '}';
            });
            yield '}';
        });
    }
}
