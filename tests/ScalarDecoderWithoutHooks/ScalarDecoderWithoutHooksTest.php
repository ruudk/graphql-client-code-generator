<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoderWithoutHooks;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoderWithoutHooks\Generated\Query\Test\TestQuery;
use Symfony\Component\TypeInfo\Type;

/**
 * A schema without hooks still passes the decoders from the query to every class that decodes.
 */
final class ScalarDecoderWithoutHooksTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()
            ->withScalar('ID', Type::string(), Type::object(NodeId::class), inputType: Type::object(NodeId::class))
            ->withScalarDecoder('ID', NodeIdDecoder::class, 'decode', service: 'app.node_id_decoder')
            ->withScalarFieldType('ID', static fn(string $type, string $field): ?Type => $type === 'User'
                && $field === 'id'
                    ? Type::object(UserId::class)
                    : null)
            ->enableSymfonyAutowireDecoders();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery(): void
    {
        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'viewer' => [
                        'id' => base64_encode('User:1'),
                        'name' => 'Ruud',
                        'manager' => [
                            'id' => base64_encode('User:4'),
                        ],
                    ],
                ],
            ]),
            [
                'ID' => new NodeIdDecoder(),
            ],
        )->execute();

        self::assertEquals(new UserId(1), $result->viewer->id);
        self::assertEquals(new UserId(4), $result->viewer->manager?->id);
    }
}
