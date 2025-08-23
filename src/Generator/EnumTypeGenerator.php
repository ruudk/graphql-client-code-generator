<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\EnumClassPlan;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

final class EnumTypeGenerator extends AbstractGenerator
{
    public function generate(EnumClassPlan $plan) : string
    {
        $name = $plan->typeName;
        $type = $plan->enumType;
        $generator = new CodeGenerator($this->fullyQualified('Enum'));

        return $generator->dumpFile(function () use ($type, $name, $generator) {
            yield $this->dumpHeader();
            yield '';
            yield from $generator->docComment('@api');

            if ($this->config->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('enum %s: string', $name);
            yield '{';
            yield $generator->indent(function () use ($generator, $type) {
                foreach ($type->getValues() as $value) {
                    Assert::string($value->value, 'Enum value must be a string');

                    if ($value->description !== null) {
                        yield from $generator->comment($value->description);
                    }

                    yield sprintf("case %s = '%s';", u($value->value)->lower()->pascal()->toString(), $value->value);

                    if ($value->description !== null) {
                        yield '';
                    }
                }

                if ($this->config->addUnknownCaseToEnums) {
                    yield '';
                    yield from $generator->comment('When the server returns an unknown enum value, this is the value that will be used.');
                    yield 'case Unknown__ = \'unknown__\';';
                }

                if ($this->config->dumpMethods) {
                    $numberOfValues = count($type->getValues());
                    foreach ($type->getValues() as $value) {
                        Assert::string($value->value, 'Enum value must be a string');

                        yield '';
                        yield sprintf('public function is%s() : bool', u($value->value)->lower()->pascal()->toString());
                        yield '{';
                        yield $generator->indent(function () use ($generator, $numberOfValues, $value) {
                            if ($numberOfValues === 1) {
                                yield from $generator->comment('@phpstan-ignore identical.alwaysTrue');
                            }

                            yield sprintf(
                                'return $this === self::%s;',
                                u($value->value)->lower()->pascal()->toString(),
                            );
                        });
                        yield '}';

                        yield '';
                        yield sprintf(
                            'public static function create%s() : self',
                            u($value->value)->lower()->pascal()->toString(),
                        );
                        yield '{';
                        yield $generator->indent(function () use ($value) {
                            yield sprintf('return self::%s;', u($value->value)->lower()->pascal()->toString());
                        });
                        yield '}';
                    }
                }
            });
            yield '}';
        });
    }
}
