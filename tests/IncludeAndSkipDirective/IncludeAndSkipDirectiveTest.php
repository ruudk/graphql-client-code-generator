<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\TestQuery;

final class IncludeAndSkipDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableDumpOrThrows()
            ->enableDumpMethods();
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
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
                'user2' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
                'admin' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
                'admin2' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
            ],
        ]))->execute(true, false);
        self::assertSame('Ruud Kamphuis', $result->viewer->name);
        self::assertSame('Ruud Kamphuis', $result->user2->name);
        self::assertNotNull($result->admin);
        self::assertSame('Ruud Kamphuis', $result->admin->name);
        self::assertNotNull($result->admin2);
        self::assertSame('Ruud Kamphuis', $result->admin2->name);
    }
}
