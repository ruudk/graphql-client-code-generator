<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\TypeInitializer;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class HooksWithCustomTypeInitializerTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withHook(FindUserByIdHook::class)
            ->withObjectType(
                'Url',
                Type::arrayShape([
                    'href' => [
                        'type' => Type::string(),
                        'optional' => false,
                    ],
                ]),
                Type::object(Url::class),
            )
            ->withIgnoreType('Url')
            ->withTypeInitializer(
                new class implements TypeInitializer {
                    #[Override]
                    public function supports(Type $type) : bool
                    {
                        return $type instanceof ObjectType && $type->getClassName() === Url::class;
                    }

                    /**
                     * @return Generator<string>
                     */
                    #[Override]
                    public function initialize(Type $type, CodeGenerator $generator, string $variable, DelegatingTypeInitializer $delegator) : Generator
                    {
                        yield sprintf('new %s(%s[\'href\'])', $generator->import(Url::class), $variable);
                    }
                },
            );
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $findUserById = new FindUserByIdHook([
            'user-123' => new User('user-123', 'Alice'),
            'user-456' => new User('user-456', 'Bob'),
        ]);

        $result = new TestQuery(
            $this->getClient($this->getResponseData()),
            [
                'findUserById' => $findUserById,
            ],
        )->execute();

        self::assertSame('ruudk', $result->viewer->login);
        self::assertSame('https://example.com', $result->viewer->homepage->href);
        self::assertCount(2, $result->viewer->projects);

        [$first, $second] = $result->viewer->projects;

        self::assertSame('GraphQL Code Generator', $first->name);
        self::assertSame('user-123', $first->creator->id);
        self::assertNotNull($first->user);
        self::assertSame('Alice', $first->user->name);

        self::assertSame('Some Other Project', $second->name);
        self::assertSame('user-456', $second->creator->id);
        self::assertNotNull($second->user);
        self::assertSame('Bob', $second->user->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function getResponseData() : array
    {
        return [
            'data' => [
                'viewer' => [
                    'login' => 'ruudk',
                    'homepage' => [
                        'href' => 'https://example.com',
                    ],
                    'projects' => [
                        [
                            'name' => 'GraphQL Code Generator',
                            'creator' => [
                                'id' => 'user-123',
                            ],
                        ],
                        [
                            'name' => 'Some Other Project',
                            'creator' => [
                                'id' => 'user-456',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
