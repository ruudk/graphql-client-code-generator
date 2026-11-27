<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Deprecation;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Enum\Role;
use Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class DeprecationTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()
            ->enableFlattenDirective()
            ->enableThrowWhenNullDirective()
            ->enableDumpOrThrowProperties();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    /**
     * Reading deprecated fields still works, and Mago reports every read (the expectations below
     * fail when the generated code stops marking them as deprecated).
     *
     * @mago-expect analysis:deprecated-property(6)
     * @mago-expect analysis:deprecated-class-constant
     */
    public function testDeprecatedFieldsStillWork(): void
    {
        $data = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'name' => 'Ruud',
                    'login' => 'ruudk',
                    'username' => 'ruudk',
                    'nickname' => 'Ruudje',
                    'knownNickname' => 'Ruudje',
                    'role' => 'EDITOR',
                    'profile' => [
                        'bio' => 'Hello',
                    ],
                ],
                'legacyViewer' => null,
            ],
        ]))->execute();

        self::assertSame('Ruud', $data->viewer->name);
        self::assertSame('ruudk', $data->viewer->login);
        self::assertSame('ruudk', $data->viewer->username);
        self::assertSame('Ruudje', $data->viewer->nickname);
        self::assertSame('Ruudje', $data->viewer->knownNickname);
        self::assertSame('Hello', $data->viewer->bio);
        self::assertSame(Role::Editor, $data->viewer->role);
        self::assertNull($data->legacyViewer);
    }
}
