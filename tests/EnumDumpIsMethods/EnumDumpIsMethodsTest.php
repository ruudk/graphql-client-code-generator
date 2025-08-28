<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\EnumDumpIsMethods;

use Override;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\EnumDumpIsMethods\Generated\Enum\Role;
use Ruudk\GraphQLCodeGenerator\EnumDumpIsMethods\Generated\Enum\State;
use Ruudk\GraphQLCodeGenerator\EnumDumpIsMethods\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Symfony\Component\TypeInfo\Type;

final class EnumDumpIsMethodsTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableAddUnknownCaseToEnums()
            ->enableDumpEnumIsMethods()
            ->withEnumType('Priority', Type::enum(CustomPriority::class));
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'accountStatus' => 'ACTIVE',
                'role' => 'ADMIN',
                'otherRole' => 'SUPERADMIN',
                'priority' => 'HIGH',
            ],
        ]))->execute();

        self::assertSame(State::Active, $result->accountStatus);
        self::assertSame(Role::Admin, $result->role);
        self::assertSame(Role::Unknown__, $result->otherRole);
        self::assertSame(CustomPriority::High, $result->priority);

        // Test that the is methods exist and work correctly
        self::assertTrue($result->accountStatus->isActive());
        self::assertTrue($result->role->isAdmin());
        self::assertFalse($result->role->isUser());
    }

    public function testCustomEnumMapping() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'accountStatus' => 'ACTIVE',
                'role' => 'USER',
                'otherRole' => 'USER',
                'priority' => 'URGENT',
            ],
        ]))->execute();

        self::assertSame(CustomPriority::Urgent, $result->priority);
    }
}
