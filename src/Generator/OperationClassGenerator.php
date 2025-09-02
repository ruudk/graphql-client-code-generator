<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use JsonException;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\OperationClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Source\FileSource;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class OperationClassGenerator extends AbstractGenerator
{
    /**
     * @throws JsonException
     */
    public function generate(OperationClassPlan $plan) : string
    {
        $namespace = $this->fullyQualified($plan->operationType, $plan->operationNamepaceName);
        $failedException = $this->fullyQualified(
            $plan->operationType,
            $plan->operationNamepaceName,
            $plan->operationName . $plan->operationType . 'FailedException',
        );

        $generator = new CodeGenerator($namespace);

        return $generator->dumpFile(function () use ($generator, $plan, $namespace, $failedException) {
            yield $this->dumpHeader();

            yield '';

            if ($this->config->addGeneratedAttribute) {
                yield from $generator->dumpAttribute(Generated::class, function () use ($generator, $plan) {
                    if ($plan->source instanceof FileSource) {
                        yield sprintf('source: %s', var_export($plan->source->relativeFilePath, true));

                        return;
                    }

                    yield sprintf('source: %s', $generator->dumpClassReference($plan->source->class));
                    yield 'restricted: true';
                });
            }

            yield sprintf('final readonly class %s {', $plan->className);
            yield $generator->indent(
                function () use ($plan, $failedException, $namespace, $generator) {
                    yield sprintf('public const string OPERATION_NAME = %s;', var_export($plan->operationName, true));
                    yield sprintf(
                        'public const string OPERATION_DEFINITION = %s;',
                        $generator->maybeNowDoc($plan->operationDefinition, 'GRAPHQL'),
                    );

                    yield '';
                    yield 'public function __construct(';
                    yield $generator->indent([
                        sprintf('private %s $client,', $generator->import($this->config->client)),
                    ]);
                    yield ') {}';

                    $parameters = $generator->indent(function () use ($plan, $generator) {
                        foreach ($plan->variables as $name => $phpType) {
                            yield sprintf(
                                '%s $%s%s,',
                                $this->dumpPHPType($phpType, $generator->import(...)),
                                $name,
                                $phpType instanceof SymfonyType\NullableType ? ' = null' : '',
                            );
                        }
                    });

                    yield '';
                    yield from $generator->docComment(function () use ($plan, $generator) {
                        foreach ($plan->variables as $name => $phpType) {
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

                    if ($plan->variables !== []) {
                        yield 'public function execute(';
                        yield $parameters;
                        yield sprintf(
                            ') : %s {',
                            $generator->import($namespace . '\\Data'),
                        );
                    } else {
                        yield sprintf(
                            'public function execute() : %s',
                            $generator->import($namespace . '\\Data'),
                        );
                        yield '{';
                    }

                    yield $generator->indent(function () use ($plan, $generator) {
                        yield '$data = $this->client->graphql(';
                        yield $generator->indent(function () use ($plan, $generator) {
                            yield 'self::OPERATION_DEFINITION,';
                            yield '[';
                            yield $generator->indent(function () use ($plan) {
                                foreach ($plan->variables as $name => $phpType) {
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
                        yield from $generator->docComment(function () use ($plan, $failedException, $generator) {
                            foreach ($plan->variables as $name => $phpType) {
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

                        if ($plan->variables !== []) {
                            yield 'public function executeOrThrow(';
                            yield $parameters;
                            yield sprintf(
                                ') : %s {',
                                $generator->import($namespace . '\\Data'),
                            );
                        } else {
                            yield sprintf(
                                'public function executeOrThrow() : %s',
                                $generator->import($namespace . '\\Data'),
                            );
                            yield '{';
                        }

                        yield $generator->indent(function () use ($plan, $failedException, $generator) {
                            yield '$data = $this->execute(';
                            yield $generator->indent(function () use ($plan) {
                                foreach ($plan->variables as $name => $phpType) {
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
                },
            );
            yield '}';
        });
    }
}
