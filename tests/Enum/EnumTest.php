<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Enum;

use Override;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Enum\Generated\Enum\Role;
use Ruudk\GraphQLCodeGenerator\Enum\Generated\Enum\State;
use Ruudk\GraphQLCodeGenerator\Enum\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Symfony\Component\TypeInfo\Type;

final class EnumTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableAddUnknownCaseToEnums()
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
