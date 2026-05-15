<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListTypename;

use ReflectionClass;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field\MultiList;
use Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field\Single;
use Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field\SoleList;

final class ListTypenameTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    /**
     * A list whose only selection is __typename is selected purely because
     * GraphQL forces at least one field; the value is never read, so the
     * property is tagged @api.
     */
    public function testSoleTypenameOnListIsApi() : void
    {
        $docComment = new ReflectionClass(SoleList::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertIsString($docComment);
        self::assertStringContainsString('@api', $docComment);
    }

    /**
     * Other fields are selected alongside __typename, so the caller does
     * read the list items: it must NOT be tagged @api.
     */
    public function testTypenameWithSiblingFieldsOnListIsNotApi() : void
    {
        $docComment = new ReflectionClass(MultiList::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertStringNotContainsString('@api', $docComment === false ? '' : $docComment);
    }

    /**
     * The rule is list-specific: a sole __typename on a non-list nested
     * object (and not a first-level mutation field) is not tagged @api.
     */
    public function testSoleTypenameOnSingleObjectIsNotApi() : void
    {
        $docComment = new ReflectionClass(Single::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertStringNotContainsString('@api', $docComment === false ? '' : $docComment);
    }
}
