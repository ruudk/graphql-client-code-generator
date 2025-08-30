<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Tests\Planner;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Planner;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use SplFileInfo;

final class NestedFragmentPayloadShapeTest extends TestCase
{
    private function createTestConfig(string $schema, string $queries) : Config
    {
        $tempDir = sys_get_temp_dir() . '/graphql-test-' . uniqid();
        mkdir($tempDir, 0777, true);
        file_put_contents($tempDir . '/schema.graphql', $schema);
        file_put_contents($tempDir . '/query.graphql', $queries);
        $config = Config::create(
            schema: $tempDir . '/schema.graphql',
            projectDir: $tempDir,
            queriesDir: $tempDir,
            outputDir: $tempDir . '/generated',
            namespace: 'Test\\Generated',
            client: 'TestClient',
        );
        register_shutdown_function(function () use ($tempDir) : void {
            // Clean up temp files
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($tempDir);
        });

        return $config;
    }

    public function testFragmentSpreadFieldsShouldMergeInNestedPayloadShapes() : void
    {
        $schema = '
            type Query { item: Item }
            type Item { id: ID! details: [Detail!]! }
            type Detail { id: ID! metadata: [Metadata!]! }
            type Metadata { key: String! value: String! }
        ';
        $queries = '
            query TestQuery { item { ...ItemWithDetails } }
            fragment ItemWithDetails on Item {
                id
                details {
                    id
                    ...DetailBasic
                    metadata { value }
                }
            }
            fragment DetailBasic on Detail {
                metadata { key }
            }
        ';
        $config = $this->createTestConfig($schema, $queries);
        $planner = new Planner($config);
        $result = $planner->plan();
        // Find the Detail class within ItemWithDetails fragment
        $detailClass = null;
        foreach ($result->classes as $class) {
            if ($class instanceof DataClassPlan && str_contains($class->path, 'ItemWithDetails/Detail.php')) {
                $detailClass = $class;

                break;
            }
        }

        self::assertNotNull($detailClass, 'Detail class should be generated');
        $payloadShapeString = (string) $detailClass->payloadShape;
        // The metadata field should have BOTH key and value
        self::assertStringContainsString(
            "'key'",
            $payloadShapeString,
            'Should include key from DetailBasic fragment. Got: ' . $payloadShapeString,
        );
        self::assertStringContainsString(
            "'value'",
            $payloadShapeString,
            'Should include value from direct selection. Got: ' . $payloadShapeString,
        );
    }

    public function testNestedFragmentReversalsFieldMerging() : void
    {
        $schema = '
            type Query { payment: Payment }
            type Payment { id: ID! payouts: [Payout!]! }
            type Payout { id: ID! reversals: [Reversal!]! }
            type Reversal { id: ID! reason: String! }
        ';
        $queries = '
            query TestQuery { payment { ...PaymentDetails } }
            fragment PaymentDetails on Payment {
                id
                payouts {
                    id
                    ...PayoutRow
                    reversals { reason }
                }
            }
            fragment PayoutRow on Payout {
                reversals { id }
            }
        ';
        $config = $this->createTestConfig($schema, $queries);
        $planner = new Planner($config);
        $result = $planner->plan();
        // Find the Payout class within PaymentDetails fragment
        $payoutClass = null;
        foreach ($result->classes as $class) {
            if ($class instanceof DataClassPlan && str_contains($class->path, 'PaymentDetails/Payout.php')) {
                $payoutClass = $class;

                break;
            }
        }

        self::assertNotNull($payoutClass, 'Payout class should be generated');
        $payloadShapeString = (string) $payoutClass->payloadShape;
        // The reversals field should have BOTH id and reason
        self::assertStringContainsString(
            "'id'",
            $payloadShapeString,
            'Should include id from PayoutRow fragment. Got: ' . $payloadShapeString,
        );
        self::assertStringContainsString(
            "'reason'",
            $payloadShapeString,
            'Should include reason from direct selection. Got: ' . $payloadShapeString,
        );
    }
}
