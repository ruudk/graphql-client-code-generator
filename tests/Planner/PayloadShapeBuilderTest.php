<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use JsonException;
use Override;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Ruudk\GraphQLCodeGenerator\TypeMapper;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class PayloadShapeBuilderTest extends TestCase
{
    private Schema $schema;
    private PayloadShapeBuilder $builder;

    #[Override]
    protected function setUp() : void
    {
        $schema = <<<'GRAPHQL'
            scalar DateTime
            scalar JSON
            type User {
                id: ID!
                name: String!
                email: String
                profile: Profile
                metadata: JSON
                lastSeen: DateTime
                createdAt: DateTime!
            }
            type Profile {
                name: String!
                age: Int
                email: String
            }
            type Transaction {
                id: ID!
                transfers: [Transfer!]!
            }
            type Transfer {
                id: ID!
                canBeCollected: Boolean!
                customer: Customer
                transferReversals: [TransferReversal!]!
                state: String!
                createdAt: String!
            }
            type Customer {
                id: ID!
                name: String!
            }
            type TransferReversal {
                id: ID!
            }
            interface Viewer {
                id: ID!
            }
            type UserViewer implements Viewer {
                id: ID!
                email: String!
                address: String
            }
            type AdminViewer implements Viewer {
                id: ID!
                email: String!
                role: String!
            }
            union SearchResult = User | Transaction | Customer
            # Interface inheritance for testing
            interface Actor {
                id: ID!
                createdAt: DateTime!
            }
            interface Person implements Actor {
                id: ID!
                createdAt: DateTime!
                firstName: String!
                lastName: String!
            }
            type RegularUser implements Person & Actor {
                id: ID!
                createdAt: DateTime!
                firstName: String!
                lastName: String!
                email: String!
            }
            interface Employee implements Person & Actor {
                id: ID!
                createdAt: DateTime!
                firstName: String!
                lastName: String!
                role: String!
                department: String
            }
            type Manager implements Employee & Person & Actor {
                id: ID!
                createdAt: DateTime!
                firstName: String!
                lastName: String!
                role: String!
                department: String
                teamSize: Int!
            }
            type Developer implements Employee & Person & Actor {
                id: ID!
                createdAt: DateTime!
                firstName: String!
                lastName: String!
                role: String!
                department: String
                languages: [String!]!
            }
            type Query {
                id: ID!
                user: User
                users: [User!]!
                transaction: Transaction
                viewer: Viewer
                search(query: String!): [SearchResult!]!
                actors: [Actor!]!
            }
            GRAPHQL;
        $this->schema = BuildSchema::build($schema);
        $typeMapper = new TypeMapper(
            $this->schema,
            [
                'ID' => [SymfonyType::string(), SymfonyType::string()],
                'String' => [SymfonyType::string(), SymfonyType::string()],
                'Int' => [SymfonyType::int(), SymfonyType::int()],
                'Float' => [SymfonyType::float(), SymfonyType::float()],
                'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
            ],
            [], // enumTypes
            [], // inputObjectTypes
            [], // objectTypes
        );
        $this->builder = new PayloadShapeBuilder(
            $this->schema,
            $typeMapper,
        );
    }

    public function testSimpleDirectSelection() : void
    {
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $this->builder->buildPayloadShape(
            $this->parseSelectionSet(<<<'GRAPHQL'
                {
                    id
                }
                GRAPHQL),
            $queryType,
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'id': string,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testNestedSelection() : void
    {
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $this->builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        id
                        user {
                            name
                            email
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'id': string,
                    'user': null|array{
                        'email': null|string,
                        'name': string,
                    },
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testListField() : void
    {
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $this->builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        users {
                            id
                            name
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'users': list<array{
                        'id': string,
                        'name': string,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testDirectSelectionAndFragmentWithSameField() : void
    {
        // Define fragments
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment TransactionDetails on Transaction {
                    transfers {
                        id
                        canBeCollected
                        transferReversals {
                            id
                        }
                    }
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $selectionSet = $this->parseSelectionSet(
            <<<'GRAPHQL'
                {
                    transaction {
                        transfers {
                            id
                            customer {
                                id
                            }
                        }
                        ...TransactionDetails
                    }
                }
                GRAPHQL
        );
        $transactionType = $this->schema->getType('Transaction');
        self::assertNotNull($transactionType);
        $transactionField = $selectionSet->selections[0];
        self::assertInstanceOf(FieldNode::class, $transactionField);
        $transactionSelectionSet = $transactionField->selectionSet;
        self::assertNotNull($transactionSelectionSet);
        $shape = $builder->buildPayloadShape($transactionSelectionSet, $transactionType);
        // The payload shape should contain ALL fields from both selections
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'transfers': list<array{
                        'canBeCollected': bool,
                        'customer': null|array{
                            'id': string,
                        },
                        'id': string,
                        'transferReversals': list<array{
                            'id': string,
                        }>,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testInlineFragmentsOnInterface() : void
    {
        $selectionSet = $this->parseSelectionSet(
            <<<'GRAPHQL'
                {
                    viewer {
                        id
                        ...on UserViewer {
                            email
                            address
                        }
                        ...on AdminViewer {
                            email
                            role
                        }
                    }
                }
                GRAPHQL
        );
        $viewerType = $this->schema->getType('Viewer');
        self::assertNotNull($viewerType);
        $viewerField = $selectionSet->selections[0];
        self::assertInstanceOf(FieldNode::class, $viewerField);
        $viewerSelectionSet = $viewerField->selectionSet;
        self::assertNotNull($viewerSelectionSet);
        $shape = $this->builder->buildPayloadShape($viewerSelectionSet, $viewerType);
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'address'?: null|string,
                    'email'?: string,
                    'id': string,
                    'role'?: string,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testMultipleFragmentsSelectingSameField() : void
    {
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment FragmentA on User {
                    profile {
                        name
                        age
                    }
                }
                fragment FragmentB on User {
                    profile {
                        name
                        email
                    }
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        ...FragmentA
                        ...FragmentB
                    }
                    GRAPHQL
            ),
            $this->schema->getType('User') ?? throw new RuntimeException('User type not found'),
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'profile': null|array{
                        'age': null|int,
                        'email': null|string,
                        'name': string,
                    },
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testTypenameField() : void
    {
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $this->builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        __typename
                        id
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    '__typename': string,
                    'id': string,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testCustomScalars() : void
    {
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $this->builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        user {
                            id
                            name
                            metadata
                            lastSeen
                            createdAt
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'user': null|array{
                        'createdAt': scalar,
                        'id': string,
                        'lastSeen': null|scalar,
                        'metadata': null|scalar,
                        'name': string,
                    },
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testComplexNestedMerging() : void
    {
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment TransactionDetails on Transaction {
                    transfers {
                        id
                        state
                        createdAt
                        customer {
                            name
                        }
                    }
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $selectionSet = $this->parseSelectionSet(
            <<<'GRAPHQL'
                    {
                        transaction {
                            transfers {
                                id
                                state
                            }
                            ...TransactionDetails
                        }
                    }
                GRAPHQL
        );
        $transactionType = $this->schema->getType('Transaction');
        self::assertNotNull($transactionType);
        $transactionField = $selectionSet->selections[0];
        self::assertInstanceOf(FieldNode::class, $transactionField);
        $transactionSelectionSet = $transactionField->selectionSet;
        self::assertNotNull($transactionSelectionSet);
        $shape = $builder->buildPayloadShape($transactionSelectionSet, $transactionType);
        // Should merge all fields from direct and fragment selection
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'transfers': list<array{
                        'createdAt': string,
                        'customer': null|array{
                            'name': string,
                        },
                        'id': string,
                        'state': string,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testUnionWithFragmentSpreads() : void
    {
        // Test that fragment spreads inside inline fragments on unions work correctly
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment TransactionFields on Transaction {
                    id
                    transfers {
                        id
                        state
                    }
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        search(query: "test") {
                            ... on Transaction {
                                ...TransactionFields
                                transfers {
                                    canBeCollected
                                }
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // The payload should contain all fields from the fragment AND the direct selection
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'search': list<array{
                        'id'?: string,
                        'transfers'?: list<array{
                            'canBeCollected': bool,
                            'id': string,
                            'state': string,
                        }>,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testDeeplyNestedWithLists() : void
    {
        // This test pushes the boundaries with:
        // 1. Lists of unions with fragments (search returns list of SearchResult union)
        // 2. Lists within lists (transfers -> transferReversals, users list)
        // 3. 4+ levels of fragment nesting (Level1→Level2→Level3→Level4)
        // 4. Same fragment used in both list and single object contexts
        // 5. Complex merging scenarios with lists of objects
        // 6. Testing nullability at different levels (nullable Customer, non-null lists)
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment Level4Customer on Customer {
                    id
                    name
                }
                fragment Level3TransferReversal on TransferReversal {
                    id
                }
                fragment Level2Transfer on Transfer {
                    id
                    state
                    customer {
                        ...Level4Customer
                    }
                    transferReversals {
                        ...Level3TransferReversal
                    }
                }
                fragment Level1Transaction on Transaction {
                    id
                    transfers {
                        ...Level2Transfer
                        canBeCollected
                    }
                }
                fragment SearchFields on SearchResult {
                    ... on User {
                        id
                        name
                        email
                    }
                    ... on Transaction {
                        ...Level1Transaction
                    }
                    ... on Customer {
                        ...Level4Customer
                    }
                }
                fragment UserFields on User {
                    id
                    name
                    email
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        # List of unions with deeply nested fragments (4 levels)
                        search(query: "nested") {
                            ...SearchFields
                            ... on Transaction {
                                # Add extra fields to test merging with Level1Transaction fragment
                                transfers {
                                    createdAt
                                    # This tests deep list nesting - list within list
                                    transferReversals {
                                        id
                                    }
                                }
                            }
                        }
                        # Single user with fragment
                        user {
                            ...UserFields
                        }
                        # List of users - testing fragments in list context
                        users {
                            ...UserFields
                            # Direct selection that overlaps with fragment
                            id
                        }
                        # Single transaction to test deep nesting without union
                        transaction {
                            ...Level1Transaction
                            # Additional transfer fields to test merging
                            transfers {
                                createdAt
                                customer {
                                    id
                                }
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // This should properly merge all deeply nested fragments
        // Testing:
        // - 4 levels of nesting: search -> Transaction -> transfers -> transferReversals
        // - Nullable fields: customer (nullable), email (nullable)
        // - Non-null lists: transfers, transferReversals, users, search results
        // - Union type with optional fields for each type
        // - Fragment merging (Level1Transaction + direct transfer fields)
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'search': list<array{
                        'email'?: null|string,
                        'id'?: string,
                        'name'?: string,
                        'transfers'?: list<array{
                            'canBeCollected': bool,
                            'createdAt': string,
                            'customer': null|array{
                                'id': string,
                                'name': string,
                            },
                            'id': string,
                            'state': string,
                            'transferReversals': list<array{
                                'id': string,
                            }>,
                        }>,
                    }>,
                    'transaction': null|array{
                        'id': string,
                        'transfers': list<array{
                            'canBeCollected': bool,
                            'createdAt': string,
                            'customer': null|array{
                                'id': string,
                                'name': string,
                            },
                            'id': string,
                            'state': string,
                            'transferReversals': list<array{
                                'id': string,
                            }>,
                        }>,
                    },
                    'user': null|array{
                        'email': null|string,
                        'id': string,
                        'name': string,
                    },
                    'users': list<array{
                        'email': null|string,
                        'id': string,
                        'name': string,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testIncludeDirective() : void
    {
        // @include(if: $var) should make fields nullable since they might not be included
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        # Always included field
                        id
                        # Field with @include directive - should be nullable
                        user @include(if: $includeUser) {
                            id
                            name
                            email
                        }
                        # Nested field with @include - parent required, nested field nullable
                        transaction {
                            id
                            transfers @include(if: $includeTransfers) {
                                id
                                state
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // User field should be optional because of @include (might not be in response)
        // Transaction field is always included (no directive)
        // Transaction.transfers should be optional because of @include (might not be in response)
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'id': string,
                    'transaction': null|array{
                        'id': string,
                        'transfers'?: list<array{
                            'id': string,
                            'state': string,
                        }>,
                    },
                    'user'?: null|array{
                        'email': null|string,
                        'id': string,
                        'name': string,
                    },
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testSkipDirective() : void
    {
        // @skip(if: $var) should make fields nullable since they might be skipped
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        # Always included field
                        id
                        # Field with @skip directive - should be nullable
                        user @skip(if: $skipUser) {
                            id
                            name
                            email @skip(if: $skipEmail)
                        }
                        # List field with @skip
                        users @skip(if: $skipUsers) {
                            id
                            name
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // User and users fields should be optional because of @skip (might not be in response)
        // Note: email inside user should also be optional due to nested @skip
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'id': string,
                    'user'?: null|array{
                        'email'?: null|string,
                        'id': string,
                        'name': string,
                    },
                    'users'?: list<array{
                        'id': string,
                        'name': string,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testDirectivesWithFragments() : void
    {
        // Test directives combined with fragments
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment UserFields on User {
                    id
                    name
                    email @include(if: $includeEmail)
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        # Fragment spread with directive
                        user {
                            ...UserFields @skip(if: $skipUserFields)
                            # This field is always included
                            id
                        }
                        # Directive on fragment spread itself
                        transaction @include(if: $includeTransaction) {
                            id
                            transfers {
                                id
                                state @skip(if: $skipState)
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // The fragment fields should be optional due to @skip/@include directives
        // email within the fragment should be optional due to @include within the fragment
        // transaction should be optional due to @include
        // state should be optional due to @skip
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'transaction'?: null|array{
                        'id': string,
                        'transfers': list<array{
                            'id': string,
                            'state'?: string,
                        }>,
                    },
                    'user': null|array{
                        'email'?: null|string,
                        'id': string,
                        'name'?: string,
                    },
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testComplexQueryWithAllFeatures() : void
    {
        // This comprehensive test covers:
        // 1. Direct field selection
        // 2. Inline fragments on interfaces (Viewer)
        // 3. Inline fragments on unions (SearchResult)
        // 4. Named fragments (BaseUserFields, ExtendedUserFields, ViewerDetails, TransactionInfo, SearchResultItem)
        // 5. Nested fragments (ExtendedUserFields calls BaseUserFields, SearchResultItem calls BaseUserFields and TransactionInfo)
        // 6. Duplicate inline fragments (inline fragments on UserViewer/AdminViewer duplicate ViewerDetails content)
        // 7. Fields selected both directly and in fragments (user.id, user.profile)
        // 8. Custom scalars (DateTime, JSON)
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment BaseUserFields on User {
                    id
                    name
                }
                fragment ExtendedUserFields on User {
                    ...BaseUserFields
                    email
                    metadata
                    profile {
                        name
                        age
                    }
                }
                fragment ViewerDetails on Viewer {
                    id
                    ... on UserViewer {
                        email
                        address
                    }
                    ... on AdminViewer {
                        email
                        role
                    }
                }
                fragment TransactionInfo on Transaction {
                    id
                    transfers {
                        id
                        state
                    }
                }
                fragment SearchResultItem on SearchResult {
                    ... on User {
                        ...BaseUserFields
                        createdAt
                    }
                    ... on Transaction {
                        ...TransactionInfo
                    }
                    ... on Customer {
                        id
                        name
                    }
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        # Direct field selection
                        id
                        # Object with fragments and direct selection
                        user {
                            id  # Direct selection (also in fragment)
                            lastSeen  # Direct selection only
                            ...ExtendedUserFields
                            profile {  # Direct selection (also in fragment)
                                email  # Additional field not in fragment
                            }
                        }
                        # Interface with inline fragments
                        viewer {
                            ...ViewerDetails
                            ... on UserViewer {  # Duplicate inline fragment
                                email  # Already in ViewerDetails fragment
                            }
                            ... on AdminViewer {
                                email  # Already in ViewerDetails fragment
                            }
                        }
                        # Union with inline fragments
                        search(query: "test") {
                            ...SearchResultItem
                            ... on User {  # Additional inline fragment
                                email
                                lastSeen
                            }
                            ... on Transaction {  # Duplicate with additional field
                                transfers {
                                    canBeCollected
                                    customer {
                                        id
                                    }
                                }
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // The payload shape should contain ALL fields from all selections merged
        // This includes:
        // - Fields from SearchResultItem fragment which has inline fragments on User/Transaction/Customer
        // - Fields from nested BaseUserFields fragment (id, name) inside SearchResultItem
        // - Fields from nested TransactionInfo fragment (id, transfers with id and state) inside SearchResultItem
        // - Additional fields from inline fragments in the query itself
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'id': string,
                    'search': list<array{
                        'createdAt'?: scalar,
                        'email'?: null|string,
                        'id'?: string,
                        'lastSeen'?: null|scalar,
                        'name'?: string,
                        'transfers'?: list<array{
                            'canBeCollected': bool,
                            'customer': null|array{
                                'id': string,
                            },
                            'id': string,
                            'state': string,
                        }>,
                    }>,
                    'user': null|array{
                        'email': null|string,
                        'id': string,
                        'lastSeen': null|scalar,
                        'metadata': null|scalar,
                        'name': string,
                        'profile': null|array{
                            'age': null|int,
                            'email': null|string,
                            'name': string,
                        },
                    },
                    'viewer': null|array{
                        'address'?: null|string,
                        'email'?: string,
                        'id': string,
                        'role'?: string,
                    },
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testInterfaceInheritance() : void
    {
        // Test basic interface inheritance with inline fragments
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $this->builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        actors {
                            id
                            ... on Actor {
                                createdAt
                            }
                            ... on Person {
                                firstName
                                lastName
                            }
                            ... on Employee {
                                role
                                department
                            }
                            ... on RegularUser {
                                email
                            }
                            ... on Manager {
                                teamSize
                            }
                            ... on Developer {
                                languages
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'actors': list<array{
                        'createdAt'?: scalar,
                        'department'?: null|string,
                        'email'?: string,
                        'firstName'?: string,
                        'id': string,
                        'languages'?: list<string>,
                        'lastName'?: string,
                        'role'?: string,
                        'teamSize'?: int,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testNestedInterfaceInheritanceWithFragments() : void
    {
        // Test interface inheritance with named fragments
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment ActorFields on Actor {
                    id
                    createdAt
                }
                fragment PersonFields on Person {
                    ...ActorFields
                    firstName
                    lastName
                }
                fragment EmployeeFields on Employee {
                    ...PersonFields
                    role
                    department
                }
                fragment ManagerFields on Manager {
                    ...EmployeeFields
                    teamSize
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        actors {
                            ... on Manager {
                                ...ManagerFields
                            }
                            ... on Developer {
                                ...EmployeeFields
                                languages
                            }
                            ... on RegularUser {
                                ...PersonFields
                                email
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // All fields should be optional since they're type-specific
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'actors': list<array{
                        'createdAt'?: scalar,
                        'department'?: null|string,
                        'email'?: string,
                        'firstName'?: string,
                        'id'?: string,
                        'languages'?: list<string>,
                        'lastName'?: string,
                        'role'?: string,
                        'teamSize'?: int,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testMixedInterfaceAndDirectSelection() : void
    {
        // Test mixing direct selection with interface fragments
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $this->builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        actors {
                            id  # Direct selection - always present
                            ... on Person {
                                firstName
                                lastName
                                ... on Employee {
                                    role
                                }
                            }
                            ... on Manager {
                                department
                                teamSize
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'actors': list<array{
                        'department'?: null|string,
                        'firstName'?: string,
                        'id': string,
                        'lastName'?: string,
                        'role'?: string,
                        'teamSize'?: int,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testComplexInterfaceInheritanceWithConditionalFragments() : void
    {
        // Test complex scenario with interface inheritance, fragments, and directives
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment CommonActorFields on Actor {
                    id
                    createdAt
                    ... on Person {
                        firstName
                        lastName
                    }
                    ... on Employee {
                        role
                        department
                    }
                }
                fragment DetailedEmployee on Employee {
                    ...CommonActorFields
                    ... on Manager {
                        teamSize
                    }
                    ... on Developer {
                        languages
                    }
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $queryType = $this->schema->getQueryType();
        self::assertNotNull($queryType);
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        actors {
                            __typename
                            ...CommonActorFields @include(if: $includeCommon)
                            ... on Employee {
                                ...DetailedEmployee @skip(if: $skipDetails)
                            }
                            ... on RegularUser {
                                email
                                firstName @include(if: $includeName)
                            }
                        }
                    }
                    GRAPHQL
            ),
            $queryType,
        );
        // Fields with directives should be optional
        // __typename is always present
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'actors': list<array{
                        '__typename': string,
                        'createdAt'?: scalar,
                        'department'?: null|string,
                        'email'?: string,
                        'firstName'?: string,
                        'id'?: string,
                        'languages'?: list<string>,
                        'lastName'?: string,
                        'role'?: string,
                        'teamSize'?: int,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testNestedFragmentSpreadMerging() : void
    {
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment TransferStateFields on Transfer {
                    transferReversals {
                        id
                    }
                }
                fragment TransferRowFields on Transfer {
                    canBeCollected
                    ...TransferStateFields
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $transferType = $this->schema->getType('Transfer');
        self::assertNotNull($transferType);
        // Test that TransferRowFields includes fields from TransferStateFields
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        ...TransferRowFields
                    }
                    GRAPHQL
            ),
            $transferType,
        );
        // The payload shape should contain fields from BOTH fragments
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'canBeCollected': bool,
                    'transferReversals': list<array{
                        'id': string,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
    }

    public function testFragmentSpreadIncludedInPayloadShape() : void
    {
        $fragments = $this->parseFragments(
            <<<'GRAPHQL'
                fragment TransactionFlowFields on Transaction {
                    transfers {
                        id
                        customer {
                            id
                        }
                    }
                }
                GRAPHQL
        );
        $builder = new PayloadShapeBuilder(
            $this->schema,
            new TypeMapper(
                $this->schema,
                [
                    'ID' => [SymfonyType::string(), SymfonyType::string()],
                    'String' => [SymfonyType::string(), SymfonyType::string()],
                    'Int' => [SymfonyType::int(), SymfonyType::int()],
                    'Float' => [SymfonyType::float(), SymfonyType::float()],
                    'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
                ],
                [],
                [],
                [],
            ),
            $fragments['definitions'],
            $fragments['types'],
        );
        $transactionType = $this->schema->getType('Transaction');
        self::assertNotNull($transactionType);
        // Test 1: Fragment spread only
        $shape = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        ...TransactionFlowFields
                    }
                    GRAPHQL
            ),
            $transactionType,
        );
        // The payload shape should contain the fields from the fragment spread
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'transfers': list<array{
                        'customer': null|array{
                            'id': string,
                        },
                        'id': string,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape->toArrayShape()),
        );
        $shape2 = $builder->buildPayloadShape(
            $this->parseSelectionSet(
                <<<'GRAPHQL'
                    {
                        id
                        transfers {
                            id
                            canBeCollected
                        }
                        ...TransactionFlowFields
                    }
                    GRAPHQL
            ),
            $transactionType,
        );
        // The payload shape should contain ALL fields from both direct selection and fragment
        self::assertSame(
            <<<'PHPDOC'
                array{
                    'id': string,
                    'transfers': list<array{
                        'canBeCollected': bool,
                        'customer': null|array{
                            'id': string,
                        },
                        'id': string,
                    }>,
                }
                PHPDOC,
            TypeDumper::dump($shape2->toArrayShape()),
        );
    }

    /**
     * Parse a selection set from a GraphQL query string
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     */
    private function parseSelectionSet(string $selection) : SelectionSetNode
    {
        $document = Parser::parse($selection);
        $operation = $document->definitions[0];
        self::assertInstanceOf(OperationDefinitionNode::class, $operation);

        return $operation->selectionSet;
    }

    /**
     * Parse fragments and return their definitions and types
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws \GraphQL\Error\InvariantViolation
     * @return array{definitions: array<string, array{FragmentDefinitionNode, list<string>}>, types: array<string, Type&NamedType>}
     */
    private function parseFragments(string $fragmentsSource) : array
    {
        $definitions = [];
        $types = [];
        foreach (Parser::parse($fragmentsSource)->definitions as $definition) {
            if ( ! $definition instanceof FragmentDefinitionNode) {
                continue;
            }

            $name = $definition->name->value;
            $definitions[$name] = [$definition, []];
            $type = $this->schema->getType($definition->typeCondition->name->value);
            self::assertInstanceOf(NamedType::class, $type);
            $types[$name] = $type;
        }

        return [
            'definitions' => $definitions,
            'types' => $types,
        ];
    }
}
