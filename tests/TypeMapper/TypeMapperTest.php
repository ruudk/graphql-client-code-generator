<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeMapper;

use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Utils\BuildSchema;
use LogicException;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Config\ScalarDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\Type\DecodedScalarType;
use Ruudk\GraphQLCodeGenerator\TypeMapper;
use Symfony\Component\TypeInfo\Type;

final class TypeMapperTest extends TestCase
{
    public function testDecodedScalarTakesTheNarrowerTypeOfTheField(): void
    {
        $type = $this->createTypeMapper(Type::object(NodeId::class))->mapGraphQLFieldTypeToPHPType(
            GraphQLType::nonNull(GraphQLType::id()),
            'User',
            'id',
        );

        self::assertInstanceOf(DecodedScalarType::class, $type);
        self::assertSame(UserId::class, $type->getClassName());
        self::assertTrue($type->narrowed);
    }

    public function testDecodedScalarWithoutANarrowerTypeTakesTheTypeOfTheScalar(): void
    {
        $type = $this->createTypeMapper(Type::object(NodeId::class))->mapGraphQLFieldTypeToPHPType(
            GraphQLType::nonNull(GraphQLType::id()),
            'Node',
            'id',
        );

        self::assertInstanceOf(DecodedScalarType::class, $type);
        self::assertSame(NodeId::class, $type->getClassName());
        self::assertFalse($type->narrowed);
    }

    public function testDecodedScalarMustBeAnObject(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains(
            'Scalar "ID" has a decoder, so Node.id must be an object type, got "string".',
        );

        $this->createTypeMapper(Type::string())->mapGraphQLFieldTypeToPHPType(
            GraphQLType::nonNull(GraphQLType::id()),
            'Node',
            'id',
        );
    }

    private function createTypeMapper(Type $idType): TypeMapper
    {
        return new TypeMapper(
            BuildSchema::build('type Query { viewer: ID! }'),
            [
                'ID' => [Type::string(), $idType],
            ],
            [],
            [],
            [],
            scalarDecoders: [
                'ID' => new ScalarDecoder('ID', NodeIdDecoder::class, 'decode'),
            ],
            scalarFieldTypes: [
                'ID' => static fn(string $type, string $field): ?Type => $type === 'User' && $field === 'id'
                    ? Type::object(UserId::class)
                    : null,
            ],
        );
    }
}
