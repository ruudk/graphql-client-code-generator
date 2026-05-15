<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\UnusedFragment;

use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Planner;
use Ruudk\GraphQLCodeGenerator\TestClient;

/**
 * Deliberately NOT a {@see \Ruudk\GraphQLCodeGenerator\GraphQLTestCase}: this
 * fixture intentionally contains unused fragments, and the auto-discovered
 * `--ensure-sync` run would (correctly) fail on them. Here we drive the
 * Planner directly and assert on the detected set instead.
 */
final class UnusedFragmentTest extends TestCase
{
    private function plan() : Planner
    {
        $projectDir = dirname(__DIR__, 2);

        $config = Config::create(
            schema: __DIR__ . '/Schema.graphql',
            projectDir: $projectDir,
            outputDir: __DIR__ . '/Generated',
            namespace: 'Ruudk\\GraphQLCodeGenerator\\UnusedFragment\\Generated',
            client: TestClient::class,
        )
            ->withQueriesDir(__DIR__ . '/graphql')
            ->withInlineProcessingDirectory(__DIR__)
            ->withTwigProcessingDirectory(__DIR__ . '/templates');

        return new Planner($config);
    }

    public function testUnusedFragmentsAreReported() : void
    {
        $result = $this->plan()->plan();

        self::assertSame(
            [
                'OnlyViaOrphan' => 'tests/UnusedFragment/graphql/Orphan.graphql',
                'OrphanFragment' => 'tests/UnusedFragment/graphql/Orphan.graphql',
                'UnusedTwig' => 'tests/UnusedFragment/templates/widget.html.twig',
            ],
            $result->unusedFragments,
        );
    }

    public function testUsedFragmentsAreNotReported() : void
    {
        $result = $this->plan()->plan();

        // Spread by the `Test` operation, so these must never be flagged.
        self::assertArrayNotHasKey('UsedFragment', $result->unusedFragments);
        self::assertArrayNotHasKey('UsedTwig', $result->unusedFragments);
    }
}
