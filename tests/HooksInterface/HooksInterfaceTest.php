<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInterface;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Query\Test\TestQuery;

final class HooksInterfaceTest extends GraphQLTestCase
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
            'user-123' => new User('user-123'),
        ]);

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'node' => [
                        '__typename' => 'Project',
                        'ownerId' => 'user-123',
                    ],
                ],
            ]),
            [
                'findOwner' => $findOwner,
            ],
        )->execute();

        self::assertNotNull($result->node->asProject);
        self::assertNotNull($result->node->asProject->owner);
        self::assertSame('user-123', $result->node->asProject->owner->id);
    }
}
