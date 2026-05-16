<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragmentTypename;

use ReflectionClass;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry\AsSupportedCountryError;
use Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry\AsUnsupportedCountryError;

final class InlineFragmentTypenameTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    /**
     * `... on SupportedCountryError { __typename }` selects nothing the
     * caller reads back, so the generated __typename property is tagged
     * @api, just like a sole __typename on a fire-and-forget mutation.
     */
    public function testSoleTypenameOnInlineFragmentIsApi() : void
    {
        $docComment = new ReflectionClass(AsSupportedCountryError::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertIsString($docComment);
        self::assertStringContainsString('@api', $docComment);
    }

    /**
     * An inline fragment that selects real fields alongside __typename is
     * data the caller asked for; __typename must NOT be tagged @api.
     */
    public function testTypenameWithSiblingFieldsIsNotApi() : void
    {
        $docComment = new ReflectionClass(AsUnsupportedCountryError::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertStringNotContainsString('@api', $docComment === false ? '' : $docComment);
    }
}
