<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use JsonException;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\OperationClassPlan;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class OperationClassGenerator extends AbstractGenerator
{
    /**
     * @throws JsonException
     */
    public function generate(OperationClassPlan $plan) : string
    {
        $operationName = $plan->operationName;
        $operationType = $plan->operationType;
        $queryClassName = $plan->queryClassName;
        $operationDefinition = $plan->operationDefinition;
        $relativeFilePath = $plan->relativeFilePath;

        // Extract just the types from the variables structure
        $variables = [];
        foreach ($plan->variables as $name => $variable) {
            $variables[$name] = $variable['type'];
        }

        $namespace = $this->fullyQualified($operationType);
        $className = $queryClassName . $operationType;
        $failedException = $this->fullyQualified($operationType, $queryClassName, $queryClassName . $operationType . 'FailedException');

        $generator = new CodeGenerator($namespace);

        return $generator->dumpFile([
            $this->dumpHeader(),
            sprintf('// Based on %s', $relativeFilePath),
            '',
            sprintf('final readonly class %s {', $className),
            $generator->indent(function () use ($failedException, $namespace, $variables, $queryClassName, $generator, $operationDefinition, $operationName) {
                yield sprintf('public const string OPERATION_NAME = %s;', var_export($operationName, true));
                yield sprintf('public const string OPERATION_DEFINITION = %s;', $generator->maybeNowDoc($operationDefinition, 'GRAPHQL'));

                yield '';
                yield 'public function __construct(';
                yield $generator->indent([
                    sprintf('private %s $client,', $generator->import($this->config->client)),
                ]);
                yield ') {}';

                $parameters = $generator->indent(function () use ($generator, $variables) {
                    foreach ($variables as $name => $phpType) {
                        yield sprintf(
                            '%s $%s%s,',
                            $this->dumpPHPType($phpType, $generator->import(...)),
                            $name,
                            $phpType instanceof SymfonyType\NullableType ? ' = null' : '',
                        );
                    }
                });

                yield '';
                yield from $generator->docComment(function () use ($generator, $variables) {
                    foreach ($variables as $name => $phpType) {
                        if ( ! $phpType instanceof SymfonyType\CollectionType) {
                            continue;
                        }

                        yield sprintf(
                            '@param %s $%s',
                            TypeDumper::dump($phpType, $generator->import(...)),
                            $name,
                        );
                    }
                });

                if ($variables !== []) {
                    yield 'public function execute(';
                    yield $parameters;
                    yield sprintf(') : %s {', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                } else {
                    yield sprintf('public function execute() : %s', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                    yield '{';
                }

                yield $generator->indent(function () use ($generator, $variables) {
                    yield '$data = $this->client->graphql(';
                    yield $generator->indent(function () use ($generator, $variables) {
                        yield 'self::OPERATION_DEFINITION,';
                        yield '[';
                        yield $generator->indent(function () use ($variables) {
                            foreach ($variables as $name => $phpType) {
                                yield sprintf("'%s' => \$%s,", $name, $name);
                            }
                        });
                        yield '],';
                        yield 'self::OPERATION_NAME,';
                    });
                    yield ');';
                    yield '';
                    yield 'return new Data(';
                    yield $generator->indent([
                        "\$data['data'] ?? [], // @phpstan-ignore argument.type",
                        "\$data['errors'] ?? [] // @phpstan-ignore argument.type",
                    ]);
                    yield ');';
                });
                yield '}';

                if ($this->config->dumpOrThrows) {
                    yield '';
                    yield from $generator->docComment(function () use ($failedException, $generator, $variables) {
                        foreach ($variables as $name => $phpType) {
                            if ( ! $phpType instanceof SymfonyType\CollectionType) {
                                continue;
                            }

                            yield sprintf(
                                '@param %s $%s',
                                TypeDumper::dump($phpType, $generator->import(...)),
                                $name,
                            );
                        }

                        yield sprintf('@throws %s', $generator->import($failedException));
                    });

                    if ($variables !== []) {
                        yield 'public function executeOrThrow(';
                        yield $parameters;
                        yield sprintf(') : %s {', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                    } else {
                        yield sprintf('public function executeOrThrow() : %s', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                        yield '{';
                    }

                    yield $generator->indent(function () use ($failedException, $generator, $variables) {
                        yield '$data = $this->execute(';
                        yield $generator->indent(function () use ($variables) {
                            foreach ($variables as $name => $phpType) {
                                yield sprintf('$%s,', $name);
                            }
                        });
                        yield ');';

                        yield '';
                        yield 'if ($data->errors !== []) {';
                        yield $generator->indent([
                            sprintf('throw new %s($data);', $generator->import($failedException)),
                        ]);
                        yield '}';

                        yield '';
                        yield 'return $data;';
                    });
                    yield '}';
                }
            }),
            '}',
        ]);
    }
}
