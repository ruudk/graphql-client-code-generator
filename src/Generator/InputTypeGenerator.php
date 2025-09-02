<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use JsonSerializable;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\InputClassPlan;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class InputTypeGenerator extends AbstractGenerator
{
    public function __construct(
        Config $config,
    ) {
        parent::__construct($config);
    }

    public function generate(InputClassPlan $plan) : string
    {
        $generator = new CodeGenerator($this->fullyQualified('Input'));

        return $generator->dumpFile(function () use ($plan, $generator) {
            yield $this->dumpHeader();

            if ($plan->description !== null) {
                yield '';
                yield from $generator->comment($plan->description);
            }

            $sortedFields = $plan->fields;
            uksort($sortedFields, function (string $a, string $b) use ($plan) : int {
                $aRequired = in_array($a, $plan->required, true);
                $bRequired = in_array($b, $plan->required, true);

                if ($aRequired === $bRequired) {
                    return 0;
                }

                return $aRequired ? -1 : 1;
            });

            yield '';

            if ($this->config->addSymfonyExcludeAttribute) {
                yield from $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('final readonly class %s implements %s', $plan->typeName, $generator->import(JsonSerializable::class));
            yield '{';
            yield $generator->indent(function () use ($sortedFields, $plan, $generator) {
                yield from $generator->docComment(function () use ($sortedFields, $generator) {
                    foreach ($sortedFields as $fieldName => $fieldType) {
                        if ($fieldType instanceof SymfonyType\CollectionType) {
                            yield sprintf('@param %s $%s', TypeDumper::dump($fieldType, $generator->import(...)), $fieldName);
                        }
                    }
                });

                yield sprintf('%s function __construct(', $plan->isOneOf ? 'private' : 'public');
                yield $generator->indent(function () use ($plan, $sortedFields, $generator) {
                    foreach ($sortedFields as $fieldName => $fieldType) {
                        yield sprintf(
                            'public %s $%s%s,',
                            $this->dumpPHPType($fieldType, $generator->import(...)),
                            $fieldName,
                            ! in_array($fieldName, $plan->required, true) ? ' = null' : '',
                        );
                    }
                });
                yield ') {}';

                if ($plan->isOneOf) {
                    foreach ($sortedFields as $fieldName => $fieldType) {
                        // For oneOf, remove nullability for factory methods
                        if ($fieldType instanceof SymfonyType\NullableType) {
                            $fieldType = $fieldType->getWrappedType();
                        }

                        yield '';
                        yield sprintf(
                            'public static function create%s(%s $%s) : self',
                            ucfirst($fieldName),
                            $this->dumpPHPType($fieldType, $generator->import(...)),
                            $fieldName,
                        );
                        yield '{';
                        yield $generator->indent(function () use ($fieldName) {
                            yield sprintf('return new self(%s: $%s);', $fieldName, $fieldName);
                        });
                        yield '}';
                    }
                }

                yield '';
                yield from $generator->docComment(sprintf(
                    '@return %s',
                    TypeDumper::dump(
                        SymfonyType::arrayShape($plan->fields),
                        $generator->import(...),
                    ),
                ));
                yield from $generator->dumpAttribute(Override::class);
                yield 'public function jsonSerialize() : array';
                yield '{';
                yield $generator->indent(function () use ($generator, $plan) {
                    yield 'return [';
                    yield $generator->indent(function () use ($plan) {
                        foreach (array_keys($plan->fields) as $fieldName) {
                            yield sprintf(
                                "'%s' => \$this->%s,",
                                $fieldName,
                                $fieldName,
                            );
                        }
                    });
                    yield '];';
                });
                yield '}';
            });
            yield '}';
        });
    }
}
