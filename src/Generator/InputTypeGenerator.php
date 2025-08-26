<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use JsonSerializable;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\InputClassPlan;
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

            yield '';

            if ($this->config->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('final readonly class %s implements %s', $plan->typeName, $generator->import(JsonSerializable::class));
            yield '{';
            yield $generator->indent(function () use ($plan, $generator) {
                $required = [];
                $optional = [];

                foreach ($plan->fields as $fieldName => $field) {
                    if ($field['required']) {
                        $required[$fieldName] = $field['type'];

                        continue;
                    }

                    $optional[$fieldName] = $field['type'];
                }

                $fields = [...$required, ...$optional];

                yield from $generator->docComment(function () use ($generator, $fields) {
                    foreach ($fields as $fieldName => $fieldType) {
                        if ( ! $fieldType instanceof SymfonyType\CollectionType) {
                            continue;
                        }

                        yield sprintf('@param %s $%s', $this->dumpPHPDocType($fieldType, $generator->import(...)), $fieldName);
                    }
                });

                yield sprintf('%s function __construct(', $plan->isOneOf ? 'private' : 'public');
                yield $generator->indent(function () use ($generator, $plan) {
                    foreach ($plan->fields as $fieldName => $field) {
                        yield sprintf(
                            'public %s $%s%s,',
                            $this->dumpPHPType($field['type'], $generator->import(...)),
                            $fieldName,
                            ! $field['required'] ? ' = null' : '',
                        );
                    }
                });
                yield ') {}';

                if ($plan->isOneOf) {
                    foreach ($plan->fields as $fieldName => $field) {
                        $fieldType = $field['type'];

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
                yield from $generator->docComment(sprintf('@return %s', $this->dumpPHPDocType(SymfonyType::arrayShape($fields), $generator->import(...))));
                yield $generator->dumpAttribute(Override::class);
                yield 'public function jsonSerialize() : array';
                yield '{';
                yield $generator->indent(function () use ($generator, $plan) {
                    yield 'return [';
                    yield $generator->indent(function () use ($plan) {
                        foreach ($plan->fields as $fieldName => $field) {
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
