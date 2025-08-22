<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type;
use JsonSerializable;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class InputTypeGenerator extends AbstractGenerator
{
    /**
     * @param callable(Type): SymfonyType $typeMapper
     */
    public function generate(InputObjectType $type, bool $isOneOf, callable $typeMapper) : string
    {
        $generator = new CodeGenerator($this->fullyQualified('Input'));

        return $generator->dumpFile(function () use ($isOneOf, $generator, $type, $typeMapper) {
            yield $this->dumpHeader();

            $description = $type->description();

            if ($description !== null) {
                yield '';
                yield from $generator->comment($description);
            }

            yield '';

            if ($this->config->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('final readonly class %s implements %s', $type, $generator->import(JsonSerializable::class));
            yield '{';
            yield $generator->indent(function () use ($isOneOf, $type, $generator, $typeMapper) {
                $required = [];
                $optional = [];

                foreach ($type->getFields() as $fieldName => $field) {
                    $fieldType = $typeMapper($field->getType());

                    if ($field->isRequired()) {
                        $required[$fieldName] = $fieldType;

                        continue;
                    }

                    $optional[$fieldName] = $fieldType;
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

                yield sprintf('%s function __construct(', $isOneOf ? 'private' : 'public');
                yield $generator->indent(function () use ($generator, $type, $typeMapper) {
                    foreach ($type->getFields() as $fieldName => $field) {
                        $fieldType = $typeMapper($field->getType());

                        yield sprintf(
                            'public %s $%s%s,',
                            $this->dumpPHPType($fieldType, $generator->import(...)),
                            $fieldName,
                            ! $field->isRequired() ? ' = null' : '',
                        );
                    }
                });
                yield ') {}';

                if ($isOneOf) {
                    foreach ($type->getFields() as $fieldName => $field) {
                        $fieldType = $field->getType();

                        if ($fieldType instanceof NullableType) {
                            $fieldType = Type::nonNull($fieldType);
                        }

                        $fieldType = $typeMapper($fieldType);

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
                yield $generator->indent(function () use ($generator, $fields) {
                    yield 'return [';
                    yield $generator->indent(function () use ($fields) {
                        foreach ($fields as $fieldName => $fieldType) {
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
