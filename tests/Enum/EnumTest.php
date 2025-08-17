<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Enum;

use Ruudk\GraphQLCodeGenerator\Enum\Expected\Enum\Role;
use Ruudk\GraphQLCodeGenerator\Enum\Expected\Enum\State;
use Ruudk\GraphQLCodeGenerator\Enum\Expected\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class EnumTest extends GraphQLTestCase
{
    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'accountStatus' => 'ACTIVE',
                'role' => 'ADMIN',
            ],
        ]))->execute();

        self::assertSame(State::Active, $result->accountStatus);
        self::assertSame(Role::Admin, $result->role);
    }
}
