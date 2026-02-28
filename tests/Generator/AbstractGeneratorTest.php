<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Generator\AbstractGenerator;
use Ruudk\GraphQLCodeGenerator\Type\ScalarType;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class AbstractGeneratorTest extends TestCase
{
    public function testNullableCustomScalarUsesExplicitNullUnionInsteadOfNullableShorthand() : void
    {
        $generator = new class(Config::create(
            schema: '',
            projectDir: '',
            queriesDir: '',
            outputDir: '',
            namespace: 'Test\\Generated',
            client: 'Test\\Client',
        )) extends AbstractGenerator {
            public function dump(SymfonyType $type) : string
            {
                return $this->dumpPHPType($type, static fn(string $className) => $className);
            }
        };

        self::assertSame(
            'null|int|string|float|bool',
            $generator->dump(SymfonyType::nullable(new ScalarType())),
            'Nullable custom scalar output should not use ? with a multi-type union.',
        );
    }
}
