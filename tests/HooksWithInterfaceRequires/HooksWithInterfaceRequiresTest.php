<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Query\Test\TestQuery;

/**
 * A hook whose `requires` fragment is conditioned on an interface (`Node`) — it
 * can be attached to any object type implementing that interface. Here the same
 * `@hook(name: "findOwner")` is used on both `Article` and `Video`.
 */
final class HooksWithInterfaceRequiresTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withHook(FindOwnerHook::class);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $findOwner = new FindOwnerHook([
            'article-1' => new Owner('article-1', 'Alice'),
            'video-1' => new Owner('video-1', 'Bob'),
        ]);

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'articles' => [
                        [
                            'id' => 'article-1',
                            'title' => 'Hello',
                        ],
                    ],
                    'videos' => [
                        [
                            'id' => 'video-1',
                            'duration' => 90,
                        ],
                    ],
                ],
            ]),
            [
                'findOwner' => $findOwner,
            ],
        )->execute();

        [$article] = $result->articles;
        self::assertSame('Hello', $article->title);
        self::assertNotNull($article->owner);
        self::assertSame('Alice', $article->owner->name);

        [$video] = $result->videos;
        self::assertSame(90, $video->duration);
        self::assertNotNull($video->owner);
        self::assertSame('Bob', $video->owner->name);
    }
}
