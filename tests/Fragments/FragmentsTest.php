<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments;

use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Enum\ProjectState;
use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class FragmentsTest extends GraphQLTestCase
{
    public function test() : void
    {
        $this->generate();
        $this->assertActualMatchesExpected();

        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'Viewer',
                    'login' => 'ruudk',
                    'projects' => [
                        [
                            '__typename' => 'Project',
                            'name' => 'GraphQL Code Generator',
                            'description' => null,
                            'state' => 'ACTIVE',
                        ],
                    ],
                ],
            ],
        ]))->execute();
        self::assertSame('ruudk', $result->viewer->viewerDetails?->login);
        self::assertCount(1, $result->viewer->projects);

        [$project] = $result->viewer->projects;
        self::assertSame('GraphQL Code Generator', $project->projectView?->name);
        self::assertSame(ProjectState::Active, $project->projectView->projectStateView?->state);
        self::assertNull($project->projectView->description);
    }
}
