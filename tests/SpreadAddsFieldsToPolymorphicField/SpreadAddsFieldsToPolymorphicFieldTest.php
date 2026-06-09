<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField;

use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

/**
 * Regression test for the case where a fragment spread at the parent's
 * selection set adds fields at the same path as a polymorphic field that
 * also has inline-fragment selections.
 *
 * The parent's payload shape correctly merges both selections, so each
 * polymorphic arm carries the spread-contributed fields (here: `id`).
 * The polymorphic-field subclass must reflect the same shape — otherwise
 * `new Item($this->data['item'])` is rejected by PHPStan because the
 * sealed arms don't accept the extra `id` key.
 */
final class SpreadAddsFieldsToPolymorphicFieldTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }
}
