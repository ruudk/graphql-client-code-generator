<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class ThrowWhenNullDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()->enableThrowWhenNullDirective();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }
}
