<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\EnumClassPlan;
use function Symfony\Component\String\u;

final class EnumTypeGenerator extends AbstractGenerator
{
    public function generate(EnumClassPlan $plan) : string
    {
        $generator = new CodeGenerator($this->fullyQualified('Enum'));

        return $generator->dumpFile(function () use ($plan, $generator) {
            yield $this->dumpHeader();
            yield '';
            yield from $generator->docComment('@api');

            if ($this->config->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('enum %s: string', $plan->typeName);
            yield '{';
            yield $generator->indent(function () use ($generator, $plan) {
                foreach ($plan->values as $name => $value) {
                    if ($value['description'] !== null) {
                        yield from $generator->comment($value['description']);
                    }

                    yield sprintf("case %s = '%s';", u($value['value'])->lower()->pascal()->toString(), $value['value']);

                    if ($value['description'] !== null) {
                        yield '';
                    }
                }

                if ($this->config->addUnknownCaseToEnums) {
                    yield '';
                    yield from $generator->comment('When the server returns an unknown enum value, this is the value that will be used.');
                    yield 'case Unknown__ = \'unknown__\';';
                }

                if ($this->config->dumpMethods) {
                    $numberOfValues = count($plan->values);
                    foreach ($plan->values as $name => $value) {
                        yield '';
                        yield sprintf('public function is%s() : bool', u($value['value'])->lower()->pascal()->toString());
                        yield '{';
                        yield $generator->indent(function () use ($generator, $numberOfValues, $value) {
                            if ($numberOfValues === 1) {
                                yield from $generator->comment('@phpstan-ignore identical.alwaysTrue');
                            }

                            yield sprintf(
                                'return $this === self::%s;',
                                u($value['value'])->lower()->pascal()->toString(),
                            );
                        });
                        yield '}';

                        yield '';
                        yield sprintf(
                            'public static function create%s() : self',
                            u($value['value'])->lower()->pascal()->toString(),
                        );
                        yield '{';
                        yield $generator->indent(function () use ($value) {
                            yield sprintf('return self::%s;', u($value['value'])->lower()->pascal()->toString());
                        });
                        yield '}';
                    }
                }
            });
            yield '}';
        });
    }
}
