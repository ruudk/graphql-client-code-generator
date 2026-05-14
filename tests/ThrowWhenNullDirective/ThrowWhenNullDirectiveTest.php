<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class ThrowWhenNullDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()->enableThrowWhenNullDirective();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }
}
