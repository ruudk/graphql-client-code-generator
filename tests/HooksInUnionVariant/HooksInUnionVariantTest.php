<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\TestQuery;

final class HooksInUnionVariantTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withHook(FindUserByIdHook::class);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testHookFieldIsNotUsedAsVariantDiscriminator() : void
    {
        $findUserById = new FindUserByIdHook([
            'thing-a' => new User('thing-a', 'https://example.com/avatars/a.png'),
        ]);

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'things' => [
                        [
                            '__typename' => 'VariantA',
                            'id' => 'thing-a',
                            'realFieldA' => 'hello',
                        ],
                        [
                            '__typename' => 'VariantB',
                            'id' => 'thing-b',
                            'realFieldB' => 'world',
                        ],
                    ],
                ],
            ]),
            [
                'findUserById' => $findUserById,
            ],
        )->execute();

        [$first, $second] = $result->things;

        self::assertNotNull($first->asVariantA);
        self::assertSame('hello', $first->asVariantA->realFieldA);
        self::assertNotNull($first->asVariantA->user);
        self::assertSame('thing-a', $first->asVariantA->user->id);
        self::assertSame('https://example.com/avatars/a.png', $first->asVariantA->user->avatar);
        self::assertNull($first->asVariantB);

        self::assertNull($second->asVariantA);
        self::assertNotNull($second->asVariantB);
        self::assertSame('world', $second->asVariantB->realFieldB);
    }
}
