<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Stringable;
use Symfony\Component\TypeInfo\Type;

final class SchemaConfigTest extends TestCase
{
    public function testScalarTakesAnInputTypeOfItsOwn(): void
    {
        $config = $this->createSchemaConfig()->withScalar(
            'ID',
            Type::string(),
            inputType: Type::object(Stringable::class),
        );

        self::assertEquals(['ID' => [Type::string(), Type::string()]], $config->scalars);
        self::assertEquals(['ID' => Type::object(Stringable::class)], $config->scalarInputTypes);
    }

    public function testScalarWithoutAnInputTypeForgetsTheOneItHad(): void
    {
        $config = $this
            ->createSchemaConfig()
            ->withScalar('ID', Type::string(), inputType: Type::object(Stringable::class))
            ->withScalar('ID', Type::string());

        self::assertSame([], $config->scalarInputTypes);
    }

    public function testScalarDecoderMustHaveTheMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'Decoder class "' . NodeIdDecoder::class . '" of scalar "ID" has no method "nope".',
        );

        $this->createSchemaConfig()->withScalarDecoder('ID', NodeIdDecoder::class, 'nope');
    }

    private function createSchemaConfig(): SchemaConfig
    {
        return SchemaConfig::create(
            schema: 'type Query { viewer: String }',
            outputDir: '/app/generated',
            namespace: 'App\\Generated',
            client: TestClient::class,
        );
    }
}
