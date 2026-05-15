<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ExplicitTypename;

use Ruudk\GraphQLCodeGenerator\ExplicitTypename\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class ExplicitTypenameTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    /**
     * When the user explicitly selects __typename it must become a public
     * property, AND the very same selection set still uses
     * `$this->data['__typename']` internally to discriminate the fragment
     * spread (`userDetails`) and the inline fragment (`asApplication`).
     */
    public function testExplicitTypenameIsPublicAndDiscriminationStillWorks() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
            ],
        ]))->execute();

        // Explicitly selected __typename is exposed as a public property.
        self::assertSame('User', $result->viewer->__typename);
        self::assertSame('Ruud Kamphuis', $result->viewer->name);

        // The discrimination sites that read $this->data['__typename'] keep working.
        self::assertTrue($result->viewer->isUserDetails);
        self::assertSame('ruudk', $result->viewer->userDetails?->login);
        self::assertFalse($result->viewer->isApplication);
        self::assertNull($result->viewer->asApplication);

        // __typename is actually sent to the server because it was selected.
        self::assertStringContainsString('__typename', $this->getLastOperation());
        self::assertStringContainsString('fragment UserDetails on User', $this->getLastOperation());
    }

    /**
     * The explicit __typename also propagates into the inline-fragment class
     * (AsApplication), which is one of the classes that discriminates on
     * `$this->data['__typename']`, so it must expose the property there too.
     */
    public function testExplicitTypenameOnInlineFragmentVariant() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'Application',
                    'name' => 'Some App',
                    'url' => 'https://example',
                ],
            ],
        ]))->execute();

        self::assertSame('Application', $result->viewer->__typename);
        self::assertTrue($result->viewer->isApplication);
        self::assertFalse($result->viewer->isUserDetails);
        self::assertNull($result->viewer->userDetails);

        $application = $result->viewer->asApplication;
        self::assertNotNull($application);
        self::assertSame('https://example', $application->url);
        self::assertSame('Some App', $application->name);
        // Public property on the inline-fragment variant class itself.
        self::assertSame('Application', $application->__typename);
    }
}
