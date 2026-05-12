<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class InlineFragmentTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableGeneratedAttribute()
            ->withInlineProcessingDirectory(__DIR__);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testMapper() : void
    {
        $mapper = new UserMapper();

        $listClient = new ListUsersClient(
            new Generated\Query\ListUsers9908fe\ListUsersQuery($this->getClient(
                [
                    'data' => [
                        'users' => [
                            [
                                'id' => '1',
                                'firstName' => 'Ruud',
                                'lastName' => 'Kamphuis',
                            ],
                            [
                                'id' => '2',
                                'firstName' => 'Jane',
                                'lastName' => 'Doe',
                            ],
                        ],
                    ],
                ],
                new GraphQLRequestMatcher(operationName: 'ListUsers'),
            )),
            $mapper,
        );

        $featuredClient = new FeaturedUsersClient(
            new Generated\Query\FeaturedUsers5a9829\FeaturedUsersQuery($this->getClient(
                [
                    'data' => [
                        'featuredUsers' => [
                            [
                                'id' => '3',
                                'firstName' => 'Featured',
                                'lastName' => 'Person',
                            ],
                        ],
                    ],
                ],
                new GraphQLRequestMatcher(operationName: 'FeaturedUsers'),
            )),
            $mapper,
        );

        self::assertSame(['Ruud Kamphuis', 'Jane Doe'], $listClient->getDisplayNames());
        self::assertSame(['Featured Person'], $featuredClient->getDisplayNames());
    }
}
