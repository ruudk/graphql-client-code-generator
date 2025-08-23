<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Enum;

use Ruudk\GraphQLCodeGenerator\Enum\Generated\Enum\Role;
use Ruudk\GraphQLCodeGenerator\Enum\Generated\Enum\State;
use Ruudk\GraphQLCodeGenerator\Enum\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class EnumTest extends GraphQLTestCase
{
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
            ],
        ]))->execute();

        self::assertSame(State::Active, $result->accountStatus);
        self::assertSame(Role::Admin, $result->role);
        self::assertSame(Role::Unknown__, $result->otherRole);
    }
}
