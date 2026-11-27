<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeprecatedPropertyReadIgnoreExtension::class)]
final class DeprecatedPropertyReadIgnoreExtensionTest extends TestCase
{
    /**
     * Runs PHPStan with phpstan/phpstan-deprecation-rules on the Deprecation fixture. The generated
     * getters read their own deprecated property, which the extension ignores; every read in the
     * test itself is still reported.
     */
    public function testOnlyReadsOutsideGeneratedCodeAreReported(): void
    {
        $command = sprintf(
            '%s analyse --configuration=%s --no-progress --error-format=json 2>/dev/null',
            escapeshellarg(dirname(__DIR__, 2) . '/vendor/bin/phpstan'),
            escapeshellarg(__DIR__ . '/Deprecation/phpstan.neon'),
        );

        $output = [];
        exec($command, $output);

        $result = json_decode(implode("\n", $output), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($result);
        self::assertIsArray($result['files']);

        $reported = [];
        foreach ($result['files'] as $file => $fileResult) {
            self::assertIsString($file);
            self::assertIsArray($fileResult);
            self::assertIsArray($fileResult['messages']);

            foreach ($fileResult['messages'] as $message) {
                self::assertIsArray($message);
                self::assertIsInt($message['line']);
                self::assertIsString($message['identifier']);

                $reported[] = sprintf(
                    '%s:%d %s',
                    str_replace(dirname(__DIR__) . '/', '', $file),
                    $message['line'],
                    $message['identifier'],
                );
            }
        }

        self::assertSame(
            [
                'Deprecation/DeprecationTest.php:56 property.deprecated',
                'Deprecation/DeprecationTest.php:57 property.deprecated',
                'Deprecation/DeprecationTest.php:58 property.deprecated',
                'Deprecation/DeprecationTest.php:59 property.deprecated',
                'Deprecation/DeprecationTest.php:60 property.deprecated',
                'Deprecation/DeprecationTest.php:61 classConstant.deprecated',
                'Deprecation/DeprecationTest.php:62 property.deprecated',
            ],
            $reported,
        );
        self::assertSame([], $result['errors']);
    }
}
