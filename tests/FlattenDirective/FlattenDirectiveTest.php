<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Basic\BasicQuery;
use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Nested\NestedQuery;
use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Strict\StrictQuery;
use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\WithFragment\WithFragmentQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class FlattenDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()->enableFlattenDirective()->enableThrowWhenNullDirective();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testBasic(): void
    {
        $result = new BasicQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'firstName' => 'Ruud',
                    'lastName' => 'Kamphuis',
                ],
            ],
        ]))->execute();

        self::assertSame('Ruud', $result->firstName);
        self::assertSame('Kamphuis', $result->lastName);
        self::assertStringNotContainsString('@flatten', $this->getLastOperation());
    }

    public function testNested(): void
    {
        $result = new NestedQuery($this->getClient([
            'data' => [
                'maybeViewer' => [
                    'bio' => null,
                    'account' => [
                        'email' => 'jane@example.com',
                        'settings' => [
                            'locale' => 'nl_NL',
                        ],
                    ],
                    'project' => [
                        'id' => '1',
                        'name' => 'Generator',
                    ],
                    'repositories' => [
                        [
                            'name' => 'graphql-client-code-generator',
                            'owner' => [
                                'ownerName' => 'Ruud',
                            ],
                        ],
                    ],
                ],
            ],
        ]))->execute();

        self::assertNull($result->bio);
        self::assertSame('jane@example.com', $result->email);
        self::assertSame('nl_NL', $result->locale);
        self::assertSame('Generator', $result->project?->name);
        self::assertNotNull($result->repositories);
        self::assertSame('Ruud', $result->repositories[0]->ownerName);
    }

    public function testNestedWithNullObjects(): void
    {
        $result = new NestedQuery($this->getClient([
            'data' => [
                'maybeViewer' => [
                    'bio' => 'Hello',
                    'account' => null,
                    'project' => null,
                    'repositories' => [],
                ],
            ],
        ]))->execute();

        self::assertSame('Hello', $result->bio);
        self::assertNull($result->email);
        self::assertNull($result->locale);
        self::assertNull($result->project);
        self::assertSame([], $result->repositories);
    }

    public function testNestedWithNullRoot(): void
    {
        $result = new NestedQuery($this->getClient([
            'data' => [
                'maybeViewer' => null,
            ],
        ]))->execute();

        self::assertNull($result->bio);
        self::assertNull($result->email);
        self::assertNull($result->locale);
        self::assertNull($result->project);
        self::assertNull($result->repositories);
    }

    public function testStrict(): void
    {
        $result = new StrictQuery($this->getClient([
            'data' => [
                'maybeViewer' => [
                    'firstName' => 'Ruud',
                    'bio' => 'Hello',
                    'account' => null,
                ],
            ],
        ]))->execute();

        self::assertSame('Ruud', $result->firstName);
        self::assertSame('Hello', $result->bio);
        self::assertNull($result->email);
    }

    public function testStrictThrowsWhenFlattenedObjectIsNull(): void
    {
        $result = new StrictQuery($this->getClient([
            'data' => [
                'maybeViewer' => null,
            ],
        ]))->execute();

        $this->expectException(NodeNotFoundException::class);

        self::fail(sprintf('Expected an exception, got "%s".', $result->firstName));
    }

    public function testStrictThrowsWhenLiftedFieldIsNull(): void
    {
        $result = new StrictQuery($this->getClient([
            'data' => [
                'maybeViewer' => [
                    'firstName' => 'Ruud',
                    'bio' => null,
                    'account' => null,
                ],
            ],
        ]))->execute();

        $this->expectException(NodeNotFoundException::class);

        self::fail(sprintf('Expected an exception, got "%s".', $result->bio));
    }

    public function testWithFragment(): void
    {
        $result = new WithFragmentQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'firstName' => 'Ruud',
                    'lastName' => 'Kamphuis',
                ],
                'organization' => null,
            ],
        ]))->execute();

        self::assertSame('Ruud', $result->userName->firstName);
        self::assertSame('Kamphuis', $result->userName->lastName);
        self::assertNull($result->organizationName);
    }
}
