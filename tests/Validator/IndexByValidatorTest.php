<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Validator;

use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class IndexByValidatorTest extends TestCase
{
    private Schema $schema;
    private IndexByValidator $validator;

    #[Override]
    protected function setUp() : void
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

        $userType = new ObjectType([
            'name' => 'User',
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
                    'resolve' => fn() => [],
                ],
                'userConnection' => [
                    'type' => $connectionType,
                    'resolve' => fn() => [],
                ],
            ],
        ]);

        $this->schema = new Schema([
            'query' => $queryType,
        ]);

        // Set up the validator with scalar mappings
        $this->validator = new IndexByValidator([
            'ID' => SymfonyType::string(),
            'String' => SymfonyType::string(),
            'Int' => SymfonyType::int(),
            'Float' => SymfonyType::float(),
            'Boolean' => SymfonyType::bool(),
        ]);
    }

    public function testValidSingleFieldIndexBy() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id") {
                    id
                    name
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidSingleFieldIndexByWithStringField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "name") {
                    id
                    name
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidNestedFieldIndexBy() : void
    {
        $query = <<<'GRAPHQL'
            query {
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

    public function testValidMultiFieldIndexBy() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id,name") {
                    id
                    name
                    email
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidMultiFieldIndexByWithSpaces() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id, name") {
                    id
                    name
                    email
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testValidMultiFieldNestedIndexBy() : void
    {
        $query = <<<'GRAPHQL'
            query {
                userConnection {
                    edges @indexBy(field: "node.id,node.name") {
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

    public function testInvalidIndexByOnNonList() : void
    {
        $query = <<<'GRAPHQL'
            query {
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

    public function testInvalidIndexByFieldNotSelected() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "name") {
                    id
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "name" is not selected in the indexBy directive', $errors[0]->getMessage());
    }

    public function testInvalidIndexByFieldDoesNotExist() : void
    {
        $query = <<<'GRAPHQL'
            query {
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

    public function testInvalidIndexByWithNullableFloatField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "score") {
                    id
                    score
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "score" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    public function testInvalidIndexByWithNonNullFloatField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "rating") {
                    id
                    rating
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('@indexBy(field: "rating") cannot be used because the field is not a valid array key type', $errors[0]->getMessage());
    }

    public function testInvalidIndexByWithNullableBooleanField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "active") {
                    id
                    active
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "active" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    public function testInvalidIndexByWithNonNullBooleanField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "verified") {
                    id
                    verified
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('@indexBy(field: "verified") cannot be used because the field is not a valid array key type', $errors[0]->getMessage());
    }

    public function testMultiFieldIndexByWithOneInvalidField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id,score") {
                    id
                    score
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "score" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    public function testMultiFieldIndexByWithOneFieldNotSelected() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id,name") {
                    id
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "name" is not selected in the indexBy directive', $errors[0]->getMessage());
    }

    public function testInvalidIndexByWithNullableStringField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "email") {
                    id
                    email
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "email" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    public function testInvalidIndexByWithNullableIntField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "age") {
                    id
                    age
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "age" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    public function testValidIndexByWithNonNullEnumField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "status") {
                    id
                    status
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testInvalidIndexByWithNullableEnumField() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "optionalStatus") {
                    id
                    optionalStatus
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "optionalStatus" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    public function testMultiFieldIndexByWithMixedNullability() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id,email") {
                    id
                    email
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(1, $errors);
        self::assertStringContainsString('Field "email" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    public function testValidMultiFieldIndexByWithNonNullFields() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id,name,status") {
                    id
                    name
                    status
                }
            }
            GRAPHQL;

        $errors = $this->validate($query);
        self::assertCount(0, $errors);
    }

    public function testIndexByWithEmptyField() : void
    {
        $query = <<<'GRAPHQL'
            query {
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

    public function testMultiFieldIndexByWithEmptyFieldInMiddle() : void
    {
        $query = <<<'GRAPHQL'
            query {
                users @indexBy(field: "id,,name") {
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

    public function testIndexByWithoutFieldArgument() : void
    {
        $query = <<<'GRAPHQL'
            query {
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

    public function testIndexByWithWrongArgumentName() : void
    {
        $query = <<<'GRAPHQL'
            query {
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

    public function testIndexByOnNullableList() : void
    {
        $query = <<<'GRAPHQL'
            query {
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

    public function testNestedIndexByWithDeepPath() : void
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
                    'resolve' => fn() => [],
                ],
            ],
        ]);

        $schema = new Schema([
            'query' => $queryType,
        ]);

        $query = <<<'GRAPHQL'
            query {
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

    public function testInvalidIndexByWithNullableIntermediateField() : void
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
                    'resolve' => fn() => [],
                ],
            ],
        ]);

        $schema = new Schema([
            'query' => $queryType,
        ]);

        $query = <<<'GRAPHQL'
            query {
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
        self::assertStringContainsString('Field "optionalAddress" is nullable and cannot be used for @indexBy', $errors[0]->getMessage());
    }

    /**
     * @return array<Error>
     */
    private function validate(string $query) : array
    {
        $document = Parser::parse($query);

        return DocumentValidator::validate($this->schema, $document, [$this->validator]);
    }
}
