<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class FragmentBugTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'projects' => [
                    [
                        'name' => 'Project 1',
                        'creator' => [
                            '__typename' => 'User',
                            'id' => '111',
                            'name' => 'Ruud',
                        ],
                    ],
                    [
                        'name' => 'Project 2',
                        'creator' => [
                            '__typename' => 'Admin',
                            'id' => '222',
                            'name' => 'Super admin',
                            'role' => 'GOD',
                        ],
                    ],
                ],
            ],
        ]))->execute();

        self::assertCount(2, $result->projects);

        [$project1, $project2] = $result->projects;
        self::assertSame('Project 1', $project1->projectView->name);
        self::assertSame('111', $project1->projectView->projectStateView->creator->id);
        self::assertNotNull($project1->projectView->creator->asUser);
        self::assertSame('Ruud', $project1->projectView->creator->asUser->name);

        self::assertSame('Project 2', $project2->projectView->name);
        self::assertSame('222', $project2->projectView->projectStateView->creator->id);
        self::assertNotNull($project2->projectView->creator->asAdmin);
        self::assertSame('Super admin', $project2->projectView->creator->asAdmin->name);
        self::assertSame('GOD', $project2->projectView->creator->asAdmin->role);
    }
}
