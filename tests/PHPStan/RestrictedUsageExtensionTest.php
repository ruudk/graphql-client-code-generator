<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RestrictedUsageExtension::class)]
final class RestrictedUsageExtensionTest extends TestCase
{
    public function testExtension() : void
    {
        $command = sprintf(
            '%s analyse --configuration=%s --no-progress --error-format=json 2>/dev/null',
            escapeshellarg(dirname(__DIR__, 2) . '/vendor/bin/phpstan'),
            escapeshellarg(__DIR__ . '/phpstan.neon'),
        );

        exec($command, $output);

        $actual = str_replace(__DIR__ . '/', '', implode("\n", $output));

        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
                {
                    "totals": {"errors": 0, "file_errors": 4},
                    "files": {
                        "Fixtures/NotAllowedController.php": {
                            "errors": 4,
                            "messages": [
                                {
                                    "message": "Method execute from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\SomeQuery is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                    "line": 18,
                                    "ignorable": true,
                                    "identifier": "graphql.inline.method.restricted"
                                },
                                {
                                    "message": "Instantiation of Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\Data is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                    "line": 19,
                                    "ignorable": true,
                                    "identifier": "new.graphql.inline.class.restricted"
                                },
                                {
                                    "message": "Property name from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\Data is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                    "line": 21,
                                    "ignorable": true,
                                    "identifier": "graphql.inline.property.restricted"
                                },
                                {
                                    "message": "Property name from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\Data is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                    "line": 21,
                                    "ignorable": true,
                                    "identifier": "graphql.inline.property.restricted"
                                }
                            ]
                        }
                    },
                    "errors": []
                }
                JSON,
            $actual,
        );
    }
}
