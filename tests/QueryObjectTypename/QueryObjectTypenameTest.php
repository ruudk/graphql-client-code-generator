<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\QueryObjectTypename;

use ReflectionClass;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\Order\AsMarketPlaceOrderItem\FxFee;
use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\SupportedCountry\Error;

final class QueryObjectTypenameTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    /**
     * `error { __typename }` on a regular (non-list) query object field is
     * the idiomatic way to probe an object's presence/non-null without
     * reading any data back, so the generated __typename property is tagged
     * @api to keep dead-code analysis from flagging it.
     */
    public function testSoleTypenameOnQueryObjectFieldIsApi() : void
    {
        $docComment = new ReflectionClass(Error::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertIsString($docComment);
        self::assertStringContainsString('@api', $docComment);
    }

    /**
     * A sole __typename on an object field nested inside an inline fragment
     * is generated via the regular field path; it is also never read back,
     * so it is tagged @api too.
     */
    public function testSoleTypenameOnNestedObjectFieldInsideInlineFragmentIsApi() : void
    {
        $docComment = new ReflectionClass(FxFee::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertIsString($docComment);
        self::assertStringContainsString('@api', $docComment);
    }
}
