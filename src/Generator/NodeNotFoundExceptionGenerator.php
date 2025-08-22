<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Exception;
use Ruudk\CodeGenerator\CodeGenerator;

final class NodeNotFoundExceptionGenerator extends AbstractGenerator
{
    public function generate() : string
    {
        $generator = new CodeGenerator($this->config->namespace);

        return $generator->dumpFile(function () use ($generator) {
            yield $this->dumpHeader();

            yield '';
            yield sprintf('final class NodeNotFoundException extends %s', $generator->import(Exception::class));
            yield '{';
            yield $generator->indent(function () use ($generator) {
                yield 'public static function create(string $node, string $property) : self';
                yield '{';
                yield $generator->indent(function () {
                    yield "return new self(sprintf('Field %s.%s is null', \$node, \$property));";
                });
                yield '}';
            });
            yield '}';
        });
    }
}
