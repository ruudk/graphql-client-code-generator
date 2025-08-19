<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexBy;

use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\IndexBy\Expected\Query\TestQuery;

final class IndexByTest extends GraphQLTestCase
{
    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'projects' => [
                    [
                        'id' => '16b6f807-689d-4d3f-9c9b-a1c774eed7ea',
                        'name' => 'GraphQL Code Generator',
                        'description' => null,
                    ],
                    [
                        'id' => '644e7023-6e09-4e18-9ef2-947de0d0ed82',
                        'name' => 'GraphQL PHP',
                        'description' => null,
                    ],
                ],
            ],
        ]))->execute();

        self::assertCount(2, $result->projects);

        self::assertArrayHasKey('16b6f807-689d-4d3f-9c9b-a1c774eed7ea', $result->projects);
        self::assertSame('GraphQL Code Generator', $result->projects['16b6f807-689d-4d3f-9c9b-a1c774eed7ea']->name);

        self::assertArrayHasKey('644e7023-6e09-4e18-9ef2-947de0d0ed82', $result->projects);
        self::assertSame('GraphQL PHP', $result->projects['644e7023-6e09-4e18-9ef2-947de0d0ed82']->name);
    }
}
