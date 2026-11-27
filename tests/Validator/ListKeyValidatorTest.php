<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Validator;

use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class ListKeyValidatorTest extends TestCase
{
    private Schema $schema;
    private ListKeyValidator $validator;

    #[Override]
    protected function setUp(): void
    {
        // Create a test schema
        $statusEnum = new EnumType([
            'name' => 'UserStatus',
            'values' => [
                'ACTIVE' => [
                    'value' => 'active',
                ],
                'INACTIVE' => [
                    'value' => 'inactive',
                ],
                'PENDING' => [
                    'value' => 'pending',
                ],
            ],
        ]);

        $nodeInterface = new InterfaceType([
            'name' => 'Node',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
            ],
        ]);

        $groupType = new ObjectType([
            'name' => 'Group',
            'interfaces' => [$nodeInterface],
            'fields' => [
                'id' => Type::nonNull(Type::int()),
            ],
        ]);

        $userType = new ObjectType([
            'name' => 'User',
            'interfaces' => [$nodeInterface],
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'name' => Type::nonNull(Type::string()),
                'email' => Type::string(), // nullable string
                'age' => Type::int(), // nullable int
                'score' => Type::float(), // nullable float
                'active' => Type::boolean(), // nullable boolean
                'rating' => Type::nonNull(Type::float()), // non-null float - invalid for indexing
                'verified' => Type::nonNull(Type::boolean()), // non-null boolean - invalid for indexing
                'status' => Type::nonNull($statusEnum), // non-null enum - valid for indexing
                'optionalStatus' => $statusEnum, // nullable enum
            ],
        ]);

        $edgeType = new ObjectType([
            'name' => 'UserEdge',
            'fields' => [
                'node' => Type::nonNull($userType),
                'cursor' => Type::string(),
            ],
        ]);

        $connectionType = new ObjectType([
            'name' => 'UserConnection',
            'fields' => [
                'edges' => Type::listOf($edgeType),
            ],
        ]);

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'users' => [
                    'type' => Type::listOf($userType),
                    'resolve' => static fn() => [],
                ],
                'userConnection' => [
                    'type' => $connectionType,
                    'resolve' => static fn() => [],
                ],
                'nodes' => [
                    'type' => Type::listOf(Type::nonNull($nodeInterface)),
                    'resolve' => static fn() => [],
                ],
            ],
        ]);

        $this->schema = new Schema([
            'query' => $queryType,
            'types' => [$groupType],
        ]);

        // Set up the validator with scalar mappings
        $this->validator = new ListKeyValidator([
            'ID' => SymfonyType::string(),
            'String' => SymfonyType::string(),
            'Int' => SymfonyType::int(),
            'Float' => SymfonyType::float(),
            'Boolean' => SymfonyType::bool(),
        ], ['indexBy', 'groupBy']);
    }

    public function testValidSingleFieldIndexBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "id") {
                id
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidSingleFieldIndexByWithStringField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "name") {
                id
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidNestedFieldIndexBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection {
                edges @indexBy(field: "node.id") {
                  node {
                    id
                    name
                  }
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidMultiFieldIndexBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: ["id", "name"]) {
                id
                name
                email
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByWithCommaSeparatedFields(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "id,name") {
                id
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '@indexBy takes a list of fields, like @indexBy(field: ["a", "b"]), not a comma-separated string',
            $errors[0]->getMessage(),
        );
    }

    public function testValidMultiFieldNestedIndexBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection {
                edges @indexBy(field: ["node.id", "node.name"]) {
                  node {
                    id
                    name
                  }
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByOnNonList(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection @indexBy(field: "edges") {
                edges {
                  node {
                    id
                  }
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('@indexBy can only be used on lists', $errors[0]->getMessage());
    }

    public function testInvalidIndexByFieldNotSelected(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "name") {
                id
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "name" is not selected in the indexBy directive',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByFieldDoesNotExist(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "nonexistent") {
                id
                name
                nonexistent
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertGreaterThanOrEqual(1, count($errors));
        self::assertStringContainsString('Field "nonexistent" is not defined for type', $errors[0]->getMessage());
    }

    public function testInvalidIndexByWithNullableFloatField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "score") {
                id
                score
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "score" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByWithNonNullFloatField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "rating") {
                id
                rating
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '@indexBy(field: "rating") cannot be used because the field is not a valid array key type',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByWithNullableBooleanField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "active") {
                id
                active
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "active" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByWithNonNullBooleanField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "verified") {
                id
                verified
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '@indexBy(field: "verified") cannot be used because the field is not a valid array key type',
            $errors[0]->getMessage(),
        );
    }

    public function testMultiFieldIndexByWithOneInvalidField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: ["id", "score"]) {
                id
                score
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "score" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testMultiFieldIndexByWithOneFieldNotSelected(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: ["id", "name"]) {
                id
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "name" is not selected in the indexBy directive',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByWithNullableStringField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "email") {
                id
                email
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "email" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByWithNullableIntField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "age") {
                id
                age
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "age" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testValidIndexByWithNonNullEnumField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "status") {
                id
                status
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByWithNullableEnumField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "optionalStatus") {
                id
                optionalStatus
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "optionalStatus" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testMultiFieldIndexByWithMixedNullability(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: ["id", "email"]) {
                id
                email
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "email" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testValidMultiFieldIndexByWithNonNullFields(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: ["id", "name", "status"]) {
                id
                name
                status
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testIndexByWithEmptyField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "") {
                id
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Empty field in @indexBy directive', $errors[0]->getMessage());
    }

    public function testMultiFieldIndexByWithEmptyFieldInMiddle(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: ["id", "", "name"]) {
                id
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        // Two errors: one for each empty field, one for name not selected
        self::assertGreaterThanOrEqual(1, count($errors));
        self::assertStringContainsString('Empty field in @indexBy directive', $errors[0]->getMessage());
    }

    public function testIndexByWithoutFieldArgument(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy {
                id
                name
              }
            }
            GRAPHQL;

        // Without field argument, the validator should skip
        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testIndexByWithWrongArgumentName(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(value: "id") {
                id
                name
              }
            }
            GRAPHQL;

        // With wrong argument name, the validator should skip
        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testIndexByOnNullableList(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection {
                edges @indexBy(field: "node.id") {
                  node {
                    id
                  }
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testNestedIndexByWithDeepPath(): void
    {
        // Create a more complex schema for this test
        $addressType = new ObjectType([
            'name' => 'Address',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'street' => Type::string(),
                'zipCode' => Type::nonNull(Type::string()),
            ],
        ]);

        $userWithAddressType = new ObjectType([
            'name' => 'UserWithAddress',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'address' => Type::nonNull($addressType),
                'optionalAddress' => $addressType, // nullable
            ],
        ]);

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'usersWithAddress' => [
                    'type' => Type::listOf($userWithAddressType),
                    'resolve' => static fn() => [],
                ],
            ],
        ]);

        $schema = new Schema([
            'query' => $queryType,
        ]);

        $query = <<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: "address.id") {
                id
                address {
                  id
                  street
                }
              }
            }
            GRAPHQL;

        $document = Parser::parse($query);
        $errors = DocumentValidator::validate($schema, $document, [$this->validator]);
        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByWithNullableIntermediateField(): void
    {
        // Create a schema with nullable intermediate field
        $addressType = new ObjectType([
            'name' => 'Address',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'street' => Type::string(),
            ],
        ]);

        $userWithAddressType = new ObjectType([
            'name' => 'UserWithAddress',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'optionalAddress' => $addressType, // nullable intermediate field
            ],
        ]);

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'usersWithAddress' => [
                    'type' => Type::listOf($userWithAddressType),
                    'resolve' => static fn() => [],
                ],
            ],
        ]);

        $schema = new Schema([
            'query' => $queryType,
        ]);

        $query = <<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: "optionalAddress.id") {
                id
                optionalAddress {
                  id
                }
              }
            }
            GRAPHQL;

        $document = Parser::parse($query);
        $errors = DocumentValidator::validate($schema, $document, [$this->validator]);

        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "optionalAddress" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testValidIndexByFieldSelectedInFragmentSpread(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "id") {
                ...UserFields
              }
            }

            fragment UserFields on User {
              id
              name
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidIndexByFieldSelectedInInlineFragment(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "id") {
                ... on User {
                  id
                }
                ... {
                  name
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidNestedIndexByFieldSelectedInFragments(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection {
                edges @indexBy(field: "node.id") {
                  node {
                    ...UserId
                  }
                }
              }
            }

            fragment UserId on User {
              ... on Node {
                id
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByFieldOnlySelectedForSomeTypes(): void
    {
        $query = <<<'GRAPHQL'
            {
              nodes @indexBy(field: "id") {
                ... on User {
                  id
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "id" is not selected in the indexBy directive',
            $errors[0]->getMessage(),
        );
    }

    public function testValidIndexByFieldSelectedOnInterface(): void
    {
        $query = <<<'GRAPHQL'
            {
              nodes @indexBy(field: "id") {
                ... on Node {
                  id
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByFieldOnlySelectedWithAlias(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "id") {
                userId: id
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "id" is not selected in the indexBy directive',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByEnumFieldNotSelected(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "status") {
                id
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "status" is not selected in the indexBy directive',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByWithCommaSeparatedFieldsInList(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: ["id,name"]) {
                id
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '@indexBy takes a list of fields, like @indexBy(field: ["a", "b"]), not a comma-separated string',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidIndexByWithEmptyList(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: []) {
                id
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('@indexBy needs at least one field', $errors[0]->getMessage());
    }

    public function testValidGroupBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: "status") {
                id
                status
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidNestedGroupBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection {
                edges @groupBy(field: "node.name") {
                  node {
                    name
                  }
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidGroupByOnNonList(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection @groupBy(field: "edges") {
                edges {
                  cursor
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('@groupBy can only be used on lists', $errors[0]->getMessage());
    }

    public function testInvalidGroupByWithNullableField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: "email") {
                email
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "email" is nullable and cannot be used for @groupBy',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidGroupByFieldNotSelected(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: "status") {
                id
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "status" is not selected in the groupBy directive',
            $errors[0]->getMessage(),
        );
    }

    public function testValidMultiFieldGroupBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: ["status", "name"]) {
                status
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidMultiFieldNestedGroupBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection {
                edges @groupBy(field: ["node.id", "node.status"]) {
                  node {
                    id
                    status
                  }
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testMultiFieldGroupByWithOneFieldNotSelected(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: ["status", "name"]) {
                status
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "name" is not selected in the groupBy directive',
            $errors[0]->getMessage(),
        );
    }

    public function testMultiFieldGroupByWithOneNullableField(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: ["status", "email"]) {
                status
                email
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "email" is nullable and cannot be used for @groupBy',
            $errors[0]->getMessage(),
        );
    }

    public function testMultiFieldGroupByWithOneInvalidKeyType(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: ["status", "rating"]) {
                status
                rating
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '@groupBy(field: "rating") cannot be used because the field is not a valid array key type',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidMultiFieldGroupByOnNonList(): void
    {
        $query = <<<'GRAPHQL'
            {
              userConnection @groupBy(field: ["edges", "edges"]) {
                edges {
                  cursor
                }
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('@groupBy can only be used on lists', $errors[0]->getMessage());
    }

    public function testValidGroupByWithSingleFieldInList(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: ["status"]) {
                status
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidGroupByWithCommaSeparatedFields(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: "status,name") {
                status
                name
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '@groupBy takes a list of fields, like @groupBy(field: ["a", "b"]), not a comma-separated string',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidGroupByWithEmptyList(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @groupBy(field: []) {
                status
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('@groupBy needs at least one field', $errors[0]->getMessage());
    }

    public function testInvalidGroupByCombinedWithIndexBy(): void
    {
        $query = <<<'GRAPHQL'
            {
              users @indexBy(field: "id") @groupBy(field: "status") {
                id
                status
              }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString(
            '@indexBy and @groupBy cannot be combined on one field',
            $errors[0]->getMessage(),
        );
    }

    public function testValidIndexByThroughThrowWhenNullObject(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: "optionalAddress.id") {
                id
                optionalAddress @throwWhenNull {
                  id
                }
              }
            }
            GRAPHQL);

        self::assertCount(0, $errors);
    }

    public function testValidIndexByWithThrowWhenNullKey(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: "address.street") {
                id
                address {
                  street @throwWhenNull
                }
              }
            }
            GRAPHQL);

        self::assertCount(0, $errors);
    }

    public function testValidMultiFieldIndexByThroughThrowWhenNullObjects(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: ["optionalAddress.id", "optionalAddress.city.name"]) {
                id
                optionalAddress @throwWhenNull {
                  id
                  city @throwWhenNull {
                    name
                  }
                }
              }
            }
            GRAPHQL);

        self::assertCount(0, $errors);
    }

    public function testValidGroupByThroughThrowWhenNullObjects(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @groupBy(field: ["optionalAddress.city.name", "address.id"]) {
                id
                address {
                  id
                }
                ...OptionalCity
              }
            }

            fragment OptionalCity on UserWithAddress {
              optionalAddress @throwWhenNull {
                city @throwWhenNull {
                  name
                }
              }
            }
            GRAPHQL);

        self::assertCount(0, $errors);
    }

    public function testValidIndexByThroughThrowWhenNullObjectAliasedToItsOwnName(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: "optionalAddress.id") {
                id
                optionalAddress: optionalAddress @throwWhenNull {
                  id
                }
              }
            }
            GRAPHQL);

        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByWhenOnlyAnAliasHasThrowWhenNull(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: "optionalAddress.id") {
                id
                optionalAddress {
                  id
                }
                address2: optionalAddress @throwWhenNull {
                  id
                }
              }
            }
            GRAPHQL);

        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "optionalAddress" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidMultiFieldIndexByWithNullableSegmentWithoutThrowWhenNull(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @indexBy(field: ["optionalAddress.id", "optionalAddress.city.name"]) {
                id
                optionalAddress @throwWhenNull {
                  id
                  city {
                    name
                  }
                }
              }
            }
            GRAPHQL);

        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "optionalAddress.city" is nullable and cannot be used for @indexBy',
            $errors[0]->getMessage(),
        );
    }

    public function testInvalidGroupByWithNullableSegmentWithoutThrowWhenNull(): void
    {
        $errors = $this->validateWithAddresses(<<<'GRAPHQL'
            {
              usersWithAddress @groupBy(field: "optionalAddress.city.name") {
                id
                optionalAddress {
                  city @throwWhenNull {
                    name
                  }
                }
              }
            }
            GRAPHQL);

        self::assertCount(1, $errors);
        self::assertStringContainsString(
            'Field "optionalAddress" is nullable and cannot be used for @groupBy',
            $errors[0]->getMessage(),
        );
    }

    /**
     * Validates against a schema of users with a non-null and a nullable address, and a nullable city.
     *
     * @return array<Error>
     */
    private function validateWithAddresses(string $query): array
    {
        $cityType = new ObjectType([
            'name' => 'City',
            'fields' => [
                'name' => Type::nonNull(Type::string()),
            ],
        ]);

        $addressType = new ObjectType([
            'name' => 'Address',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'street' => Type::string(),
                'city' => $cityType,
            ],
        ]);

        $userWithAddressType = new ObjectType([
            'name' => 'UserWithAddress',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'address' => Type::nonNull($addressType),
                'optionalAddress' => $addressType,
            ],
        ]);

        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'usersWithAddress' => [
                        'type' => Type::listOf($userWithAddressType),
                        'resolve' => static fn() => [],
                    ],
                ],
            ]),
        ]);

        return DocumentValidator::validate($schema, Parser::parse($query), [$this->validator]);
    }

    /**
     * @return array<Error>
     */
    private function validate(string $query): array
    {
        $document = Parser::parse($query);

        return DocumentValidator::validate($this->schema, $document, [$this->validator]);
    }
}
