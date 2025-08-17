<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Simple;

use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Simple\Expected\Enum\ProjectState;
use Ruudk\GraphQLCodeGenerator\Simple\Expected\Query\TestQuery;

final class SimpleTest extends GraphQLTestCase
{
    public function test() : void
    {
        $this->generate();
        $this->assertActualMatchesExpected();

        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'login' => 'ruudk',
                    'projects' => [
                        [
                            'name' => 'GraphQL Code Generator',
                            'description' => null,
                            'state' => 'ACTIVE',
                        ],
                    ],
                ],
            ],
        ]))->execute();
        self::assertSame('ruudk', $result->viewer->login);
        self::assertCount(1, $result->viewer->projects);

        [$project] = $result->viewer->projects;
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertSame(ProjectState::Active, $project->state);
        self::assertNull($project->description);
    }
}
