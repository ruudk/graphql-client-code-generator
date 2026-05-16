<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments;

use ReflectionClass;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer\AsApplication;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer\AsUser;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\TestQuery;

final class InlineFragmentsTest extends GraphQLTestCase
{
    /**
     * This query never selects __typename; it is only injected so the
     * generator can discriminate the interface at runtime. The injected
     * value must NOT leak as a public property even though discrimination
     * (isUser/asUser) still reads it from $this->data['__typename'].
     */
    public function testInjectedTypenameIsNotExposedAsProperty() : void
    {
        self::assertFalse(
            new ReflectionClass(Viewer::class)->hasProperty('__typename'),
            'Auto-injected __typename must not surface as a public property',
        );

        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
                'projects' => [],
            ],
        ]))->execute();

        self::assertTrue($result->viewer->isUser);
        self::assertSame('ruudk', $result->viewer->asUser?->login);
    }

    /**
     * The query selects `name` at the interface level. Interface/parent
     * fields are NOT merged into the inline-fragment variant classes: a
     * variant exposes only what is selected inside its own
     * `... on Type { ... }`. To read `name` on a variant it must be
     * selected within that variant.
     */
    public function testParentFieldsAreNotMergedIntoVariantClasses() : void
    {
        $asUser = new ReflectionClass(AsUser::class);
        self::assertTrue($asUser->hasProperty('login'));
        self::assertFalse($asUser->hasProperty('name'), 'Parent field "name" must not leak into AsUser');

        $asApplication = new ReflectionClass(AsApplication::class);
        self::assertTrue($asApplication->hasProperty('url'));
        self::assertFalse($asApplication->hasProperty('name'), 'Parent field "name" must not leak into AsApplication');
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
                'projects' => [
                    [
                        'name' => 'GraphQL Code Generator',
                        'description' => 'Hello, World!',
                        'state' => 'ACTIVE',
                    ],
                ],
            ],
        ]))->execute();
        self::assertSame('Ruud Kamphuis', $result->viewer->name);
        self::assertTrue($result->viewer->isUser);
        self::assertSame('ruudk', $result->viewer->asUser?->login);
        self::assertFalse($result->viewer->isApplication);
        self::assertNull($result->viewer->asApplication);
        self::assertCount(1, $result->projects);
        [$project] = $result->projects;
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertSame('Hello, World!', $project->description);
    }

    public function testApplicationViewer() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'Application',
                    'name' => 'Application',
                    'url' => 'https://example',
                ],
                'projects' => [],
            ],
        ]))->execute();
        self::assertSame('Application', $result->viewer->name);
        self::assertFalse($result->viewer->isUser);
        self::assertNull($result->viewer->asUser);
        self::assertTrue($result->viewer->isApplication);
        self::assertNotNull($result->viewer->asApplication);
        self::assertSame('https://example', $result->viewer->asApplication->url);
    }

    public function testNullWhenViewerIsDifferentType() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'Unexpected',
                    'login' => 'ruudk',
                ],
                'projects' => [],
            ],
        ]))->execute();
        self::assertFalse($result->viewer->isUser);
        self::assertNull($result->viewer->asUser);
    }
}
