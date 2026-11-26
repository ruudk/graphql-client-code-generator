<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Mago\Analyzer\Hooks\RestrictedUsageHook;
use Ruudk\GraphQLCodeGenerator\Mago\Analyzer\RestrictedUsagePlugin;

#[CoversClass(GraphQLExtension::class)]
#[CoversClass(RestrictedUsagePlugin::class)]
#[CoversClass(RestrictedUsageHook::class)]
final class RestrictedUsageHookTest extends TestCase
{
    public function testReportsRestrictedUsage() : void
    {
        $root = dirname(__DIR__, 2);

        $command = sprintf(
            '%s --workspace=%s --config=%s analyze --reporting-format=json 2>/dev/null',
            escapeshellarg($root . '/vendor/bin/mago'),
            escapeshellarg($root),
            escapeshellarg(__DIR__ . '/restricted-usage.toml'),
        );

        $output = [];
        exec($command, $output);

        $report = json_decode(implode("\n", $output), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($report);
        self::assertIsArray($report['issues'] ?? null);

        $issues = [];

        foreach ($report['issues'] as $issue) {
            self::assertIsArray($issue);
            self::assertIsArray($issue['annotations'] ?? null);
            self::assertIsArray($issue['annotations'][0] ?? null);
            self::assertIsArray($issue['annotations'][0]['span'] ?? null);

            $span = $issue['annotations'][0]['span'];

            self::assertIsArray($span['file_id'] ?? null);
            self::assertIsArray($span['start'] ?? null);
            self::assertIsString($span['file_id']['name'] ?? null);
            self::assertIsInt($span['start']['line'] ?? null);
            self::assertIsString($issue['code'] ?? null);
            self::assertIsString($issue['message'] ?? null);

            $issues[] = sprintf(
                '%s:%d %s %s',
                basename($span['file_id']['name']),
                $span['start']['line'],
                $issue['code'],
                $issue['message'],
            );
        }

        sort($issues);

        self::assertSame(
            [
                'AllowedController.php:23 graphql/restricted-instantiation Instantiation of Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig',
                'AllowedController.php:25 graphql/restricted-property Property title from Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig',
                'NotAllowedController.php:19 graphql/restricted-method Method execute from Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeQuery is only allowed to be used from within Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures\AllowedController',
                'NotAllowedController.php:20 graphql/restricted-instantiation Instantiation of Ruudk\GraphQLCodeGenerator\PHPStan\Generated\Data is only allowed to be used from within Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures\AllowedController',
                'NotAllowedController.php:21 graphql/restricted-instantiation Instantiation of Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig',
                'NotAllowedController.php:23 graphql/restricted-property Property name from Ruudk\GraphQLCodeGenerator\PHPStan\Generated\Data is only allowed to be used from within Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures\AllowedController',
                'NotAllowedController.php:23 graphql/restricted-property Property name from Ruudk\GraphQLCodeGenerator\PHPStan\Generated\Data is only allowed to be used from within Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures\AllowedController',
                'NotAllowedController.php:23 graphql/restricted-property Property title from Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeFragment is only allowed to be used from within tests/PHPStan/templates/_some_fragment.html.twig',
                'NotAllowedController.php:26 graphql/restricted-class Class Ruudk\GraphQLCodeGenerator\PHPStan\Generated\Data is only allowed to be used from within Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures\AllowedController',
            ],
            $issues,
        );
    }
}
