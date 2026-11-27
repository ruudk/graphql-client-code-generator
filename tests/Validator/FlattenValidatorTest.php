<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Validator;

use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FlattenValidatorTest extends TestCase
{
    private Schema $schema;

    #[Override]
    protected function setUp(): void
    {
        $this->schema = BuildSchema::build(<<<'GRAPHQL'
            directive @flatten on FIELD

            directive @hook(name: String!) on FIELD

            type Query {
              viewer: User!
              users: [User!]!
              node: Node
              money: Money
            }

            interface Node {
              id: ID!
            }

            type User implements Node {
              id: ID!
              name: String!
              account: Account
            }

            type Account {
              id: ID!
              name: String!
            }

            type Money {
              amount: Int!
            }
            GRAPHQL);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideValidCases(): iterable
    {
        yield 'single object' => ['{ viewer @flatten { id name } }'];
        yield 'nested flatten with alias' => ['{ viewer @flatten { id account @flatten { accountId: id } } }'];
        yield 'same field selected twice' => ['{ viewer @flatten { id } viewer @flatten { name } }'];
        yield 'fragment spread' => ['{ viewer @flatten { ...UserName } } fragment UserName on User { name }'];
        yield 'flatten inside list items' => ['{ users { account @flatten { name } } }'];
        yield '__typename inside flattened field' => ['{ __typename viewer @flatten { __typename id } }'];
    }

    #[DataProvider('provideValidCases')]
    public function testValid(string $query): void
    {
        self::assertSame([], $this->validate($query));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidCases(): iterable
    {
        yield 'list' => [
            '{ users @flatten { id } }',
            '@flatten on "users" cannot be used on a list. Only a single object can be flattened.',
        ];
        yield 'interface' => ['{ node @flatten { id } }', '@flatten on "node" requires an object type, got Node.'];
        yield 'mapped object type' => [
            '{ money @flatten { amount } }',
            '@flatten on "money" cannot be used because Money is mapped to a custom PHP type.',
        ];
        yield 'include on flattened field' => [
            '{ viewer @flatten @include(if: true) { id } }',
            '@flatten on "viewer" cannot be combined with @include or @skip.',
        ];
        yield 'skip inside flattened field' => [
            '{ viewer @flatten { id name @skip(if: true) } }',
            '@flatten on "viewer" cannot contain selections with @include or @skip.',
        ];
        yield 'inline fragment' => [
            '{ viewer @flatten { ... on User { id } } }',
            '@flatten on "viewer" cannot contain inline fragments. Use a fragment spread instead.',
        ];
        yield 'hook' => [
            '{ viewer @flatten { id name @hook(name: "x") } }',
            '@flatten on "viewer" cannot contain @hook fields.',
        ];
        yield 'mixed flatten' => [
            '{ viewer @flatten { id } viewer { name } }',
            'Field "viewer" is selected more than once, but not every selection uses @flatten.',
        ];
        yield 'clash with sibling' => [
            '{ viewer @flatten { name } name: node { id } }',
            '@flatten makes "name" clash with another field of the same name ("viewer.name" and "name"). Use an alias to rename one of them.',
        ];
        yield 'clash between nested flattens' => [
            '{ viewer @flatten { id account @flatten { id } } }',
            '@flatten makes "id" clash with another field of the same name ("viewer.id" and "viewer.account.id"). Use an alias to rename one of them.',
        ];
    }

    #[DataProvider('provideInvalidCases')]
    public function testInvalid(string $query, string $expectedMessage): void
    {
        $messages = array_map(static fn(Error $error) => $error->getMessage(), $this->validate($query));

        self::assertContains($expectedMessage, $messages);
    }

    /**
     * @return list<Error>
     */
    private function validate(string $query): array
    {
        return array_values(DocumentValidator::validate($this->schema, Parser::parse($query), [
            new FlattenValidator(['Money']),
        ]));
    }
}
