<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\MutationTypename;

use ReflectionClass;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data\FireAndForget;
use Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data\Nested\Inner;
use Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data\WithExtraFields;

final class MutationTypenameTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    /**
     * `mutation { fireAndForget { __typename } }` is the idiomatic way to
     * run a mutation without caring about the response, so the generated
     * __typename property is tagged @api.
     */
    public function testSoleTypenameOnFirstLevelMutationFieldIsApi() : void
    {
        $docComment = new ReflectionClass(FireAndForget::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertIsString($docComment);
        self::assertStringContainsString('@api', $docComment);
    }

    /**
     * When other fields are selected alongside __typename, the caller does
     * care about the response, so it must NOT be tagged @api.
     */
    public function testTypenameWithSiblingFieldsIsNotApi() : void
    {
        $docComment = new ReflectionClass(WithExtraFields::class)
            ->getProperty('__typename')
            ->getDocComment();

        // Either no doc block at all, or one without @api.
        self::assertStringNotContainsString('@api', $docComment === false ? '' : $docComment);
    }

    /**
     * A __typename selected deeper than the first mutation level is regular
     * data the caller asked for; it must NOT be tagged @api.
     */
    public function testTypenameDeeperThanFirstLevelIsNotApi() : void
    {
        $docComment = new ReflectionClass(Inner::class)
            ->getProperty('__typename')
            ->getDocComment();

        self::assertStringNotContainsString('@api', $docComment === false ? '' : $docComment);
    }
}
