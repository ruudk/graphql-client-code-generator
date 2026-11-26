<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RestrictedUsageExtension::class)]
final class RestrictedUsageExtensionTest extends TestCase
{
    public function testExtension(): void
    {
        $command = sprintf(
            '%s analyse --configuration=%s --no-progress --error-format=json 2>/dev/null',
            escapeshellarg(dirname(__DIR__, 2) . '/vendor/bin/phpstan'),
            escapeshellarg(__DIR__ . '/phpstan.neon'),
        );

        $output = [];
        exec($command, $output);

        $actual = str_replace(__DIR__ . '/', '', implode("\n", $output));

        self::assertJsonStringEqualsJsonString(<<<'JSON'
            {
                "totals": {"errors": 0, "file_errors": 9},
                "files": {
                    "Fixtures/AllowedController.php": {
                        "errors": 2,
                        "messages": [
                            {
                                "message": "Instantiation of Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig",
                                "line": 23,
                                "ignorable": true,
                                "identifier": "new.graphql.inline.class.restricted"
                            },
                            {
                                "message": "Property title from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig",
                                "line": 25,
                                "ignorable": true,
                                "identifier": "graphql.inline.property.restricted"
                            }
                        ]
                    },
                    "Fixtures/NotAllowedController.php": {
                        "errors": 7,
                        "messages": [
                            {
                                "message": "Method execute from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\SomeQuery is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                "line": 19,
                                "ignorable": true,
                                "identifier": "graphql.inline.method.restricted"
                            },
                            {
                                "message": "Instantiation of Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\Data is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                "line": 20,
                                "ignorable": true,
                                "identifier": "new.graphql.inline.class.restricted"
                            },
                            {
                                "message": "Instantiation of Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig",
                                "line": 21,
                                "ignorable": true,
                                "identifier": "new.graphql.inline.class.restricted"
                            },
                            {
                                "message": "Property name from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\Data is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                "line": 23,
                                "ignorable": true,
                                "identifier": "graphql.inline.property.restricted"
                            },
                            {
                                "message": "Property name from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\Data is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                "line": 23,
                                "ignorable": true,
                                "identifier": "graphql.inline.property.restricted"
                            },
                            {
                                "message": "Property title from Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig",
                                "line": 23,
                                "ignorable": true,
                                "identifier": "graphql.inline.property.restricted"
                            },
                            {
                                "message": "Parameter $data of method Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\NotAllowedController::accept() has typehint with Ruudk\\GraphQLCodeGenerator\\PHPStan\\Generated\\Data is only allowed to be used from within Ruudk\\GraphQLCodeGenerator\\PHPStan\\Fixtures\\AllowedController",
                                "line": 26,
                                "ignorable": true,
                                "identifier": "parameter.graphql.inline.class.restricted"
                            }
                        ]
                    }
                },
                "errors": []
            }
            JSON, $actual);
    }
}
