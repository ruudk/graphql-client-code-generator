<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use JsonException;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\OperationClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Source\GraphQLFileSource;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ClassHookUsageRegistry;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class OperationClassGenerator extends AbstractGenerator
{
    public function __construct(
        Config $config,
        private readonly ClassHookUsageRegistry $hookUsageRegistry,
    ) {
        parent::__construct($config);
    }

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

        $dataFqcn = $namespace . '\\Data';
        $usedHooks = $this->hookUsageRegistry->getHooksForClass($dataFqcn);

        $generator = new CodeGenerator($namespace);

        return $generator->dumpFile(function () use ($generator, $plan, $namespace, $failedException, $usedHooks) {
            yield $this->dumpHeader();

            yield '';

            if ($this->config->addGeneratedAttribute) {
                yield from $generator->dumpAttribute(Generated::class, function () use ($generator, $plan) {
                    if ($plan->source instanceof GraphQLFileSource) {
                        yield sprintf('source: %s', var_export($plan->source->relativeFilePath, true));

                        return;
                    }

                    yield sprintf('source: %s', $generator->dumpClassReference($plan->source->class));
                    yield 'restricted: true';
                });
            }

            yield sprintf('final readonly class %s {', $plan->className);
            yield $generator->indent(
                function () use ($plan, $failedException, $namespace, $generator, $usedHooks) {
                    yield sprintf('public const string OPERATION_NAME = %s;', var_export($plan->operationName, true));
                    yield sprintf(
                        'public const string OPERATION_DEFINITION = %s;',
                        $generator->maybeNowDoc($plan->operationDefinition, 'GRAPHQL'),
                    );

                    yield '';

                    if ($usedHooks !== []) {
                        yield from $generator->docComment(function () use ($usedHooks, $generator) {
                            yield sprintf(
                                '@param %s $hooks',
                                TypeDumper::dump($this->buildHooksShape($usedHooks), $generator->import(...)),
                            );
                        });
                    }

                    yield 'public function __construct(';
                    yield $generator->indent(function () use ($generator, $usedHooks) {
                        yield sprintf('private %s $client,', $generator->import($this->config->client));

                        if ($usedHooks === []) {
                            return;
                        }

                        if ($this->config->symfonyAutowireHooks) {
                            $autowire = $generator->import('Symfony\Component\DependencyInjection\Attribute\Autowire');
                            yield sprintf('#[%s([', $autowire);
                            yield $generator->indent(function () use ($usedHooks, $autowire, $generator) {
                                $lines = [];
                                foreach (array_keys($usedHooks) as $name) {
                                    $lines[] = sprintf(
                                        '%s => new %s(service: %s::class)',
                                        var_export($name, true),
                                        $autowire,
                                        $generator->import($this->config->hooks[$name]->class),
                                    );
                                }

                                yield implode(",\n", $lines);
                            });
                            yield '])]';
                        }

                        yield 'private array $hooks,';
                    });
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

                    yield $generator->indent(function () use ($plan, $generator, $usedHooks) {
                        yield '$data = $this->client->graphql(';
                        yield $generator->indent(function () use ($plan, $generator) {
                            yield 'self::OPERATION_DEFINITION,';
                            yield '[';
                            yield $generator->indent(function () use ($plan) {
                                foreach ($plan->variables as $name => $phpType) {
                                    if ($phpType->isIdentifiedBy(TypeIdentifier::STRING)) {
                                        if ($phpType instanceof SymfonyType\NullableType) {
                                            yield sprintf(
                                                "'%s' => \$%s !== null ? (string) \$%s : null,",
                                                $name,
                                                $name,
                                                $name,
                                            );

                                            continue;
                                        }

                                        yield sprintf(
                                            "'%s' => (string) \$%s,",
                                            $name,
                                            $name,
                                        );

                                        continue;
                                    }

                                    yield sprintf("'%s' => \$%s,", $name, $name);
                                }
                            });
                            yield '],';
                            yield 'self::OPERATION_NAME,';
                        });
                        yield ');';
                        yield '';
                        yield 'return new Data(';
                        $trailingComma = $usedHooks !== [] ? ',' : '';
                        yield $generator->indent(function () use ($trailingComma, $usedHooks) {
                            yield "\$data['data'] ?? [], // @phpstan-ignore argument.type";
                            yield sprintf(
                                "\$data['errors'] ?? []%s // @phpstan-ignore argument.type",
                                $trailingComma,
                            );

                            if ($usedHooks !== []) {
                                yield '$this->hooks,';
                            }
                        });
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
