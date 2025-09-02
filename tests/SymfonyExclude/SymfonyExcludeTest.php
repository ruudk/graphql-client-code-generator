<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SymfonyExclude;

use Override;
use ReflectionClass;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\SymfonyExclude\Generated\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\SymfonyExclude\Generated\Query\Test\TestQuery;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

final class SymfonyExcludeTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()->enableSymfonyExcludeAttribute();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'login' => 'ruudk',
                    'projects' => [
                        [
                            'name' => 'GraphQL Code Generator',
                            'description' => null,
                        ],
                    ],
                ],
            ],
        ]))->execute();
        self::assertSame('ruudk', $result->viewer->login);
        self::assertCount(1, $result->viewer->projects);
        [$project] = $result->viewer->projects;
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertNull($project->description);
    }

    public function testSymfonyExcludeAttribute() : void
    {
        $attributes = new ReflectionClass(Data::class)->getAttributes(Exclude::class);
        self::assertCount(1, $attributes, 'The Data class should have the Exclude attribute');
    }
}
