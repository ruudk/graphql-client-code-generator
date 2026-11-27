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

final class NarrowValidatorTest extends TestCase
{
    private Schema $schema;

    #[Override]
    protected function setUp(): void
    {
        $this->schema = BuildSchema::build(<<<'GRAPHQL'
            directive @narrow on INLINE_FRAGMENT

            type Query {
              node: Node
              nodes: [Node!]!
              search: [SearchResult!]!
              viewer: User!
            }

            interface Node {
              id: ID!
            }

            interface Named {
              name: String!
            }

            type User implements Node & Named {
              id: ID!
              name: String!
            }

            type Event implements Node & Named {
              id: ID!
              name: String!
            }

            union SearchResult = User | Event
            GRAPHQL);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideValidCases(): iterable
    {
        yield 'interface' => ['{ node { id ... on User @narrow { name } } }'];
        yield 'list' => ['{ nodes { ... on User @narrow { name } } }'];
        yield 'union' => ['{ search { ... on Event @narrow { name } } }'];
        yield 'fragment spread on interface' => [
            '{ node { ...NodeId ... on User @narrow { name } } } fragment NodeId on Node { id }',
        ];
        yield 'fragment spread on narrowed type' => [
            '{ node { ...UserName ... on User @narrow { id } } } fragment UserName on User { name }',
        ];
        yield 'inside a fragment definition' => [
            '{ ...Q } fragment Q on Query { node { ... on User @narrow { name } } }',
        ];
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
        yield 'object field' => [
            '{ viewer { ... on User @narrow { name } } }',
            '@narrow on "... on User" requires "viewer" to return an interface or union, got User!.',
        ];
        yield 'interface type condition' => [
            '{ node { ... on Named @narrow { name } } }',
            '@narrow on "... on Named" requires an object type.',
        ];
        yield 'other inline fragment' => [
            '{ node { ... on User @narrow { name } ... on Event { name } } }',
            '@narrow on "... on User" cannot be combined with other inline fragments on "node".',
        ];
        yield 'include' => [
            '{ node { ... on User @narrow @include(if: true) { name } } }',
            '@narrow on "... on User" cannot be combined with @include or @skip.',
        ];
        yield 'fragment spread on another type' => [
            '{ node { ...EventName ... on User @narrow { name } } } fragment EventName on Event { name }',
            '@narrow on "... on User" cannot be combined with fragment "EventName" on Event.',
        ];
        yield 'not directly inside a field' => [
            '{ node { ... on Node { ... on User @narrow { name } } } }',
            '@narrow on "... on User" can only be used on an inline fragment directly inside a field.',
        ];
        yield 'inside a fragment definition root' => [
            '{ node { ...UserNarrow } } fragment UserNarrow on Node { ... on User @narrow { name } }',
            '@narrow on "... on User" can only be used on an inline fragment directly inside a field.',
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
            new NarrowValidator(),
        ]));
    }
}
