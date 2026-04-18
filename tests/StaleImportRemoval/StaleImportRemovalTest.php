<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\StaleImportRemoval;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Executor\PlanExecutor;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Planner;

final class StaleImportRemovalTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withInlineProcessingDirectory(__DIR__);
    }

    public function testStaleImportsAreRemoved() : void
    {
        // First, create a "stale" version of the controller file with old imports
        $staleContent = <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Ruudk\GraphQLCodeGenerator\StaleImportRemoval;

            use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
            use Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewerabc123\GetViewerQuery;
            use Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewerdef456\GetViewerQuery as AliasedQuery;

            final readonly class ControllerWithStaleImport
            {
                private const string OPERATION = <<<'GRAPHQL'
                    query GetViewer {
                        viewer {
                            login
                        }
                    }
                    GRAPHQL;

                public function __construct(
                    #[GeneratedGraphQLClient(self::OPERATION)]
                    public GetViewerQuery $query,
                ) {}
            }
            PHP;

        $controllerPath = __DIR__ . '/ControllerWithStaleImport.php';
        $originalContent = file_get_contents($controllerPath);

        try {
            // Write the stale content
            file_put_contents($controllerPath, $staleContent);

            // Run the generator
            $config = $this->getConfig();
            $plan = new Planner($config)->plan();
            $files = new PlanExecutor($config)->execute($plan);

            // Check the output
            self::assertArrayHasKey($controllerPath, $files, 'Controller file should be in output');
            $output = $files[$controllerPath];

            // The stale imports should be removed
            self::assertStringNotContainsString(
                'GetViewerabc123',
                $output,
                'Stale import with old hash should be removed',
            );
            self::assertStringNotContainsString(
                'GetViewerdef456',
                $output,
                'Another stale import should also be removed',
            );

            // The correct import should be present
            $matches = [];
            preg_match_all(
                '/use Ruudk\\\\GraphQLCodeGenerator\\\\StaleImportRemoval\\\\Generated\\\\Query\\\\GetViewer[a-f0-9]+\\\\GetViewerQuery/',
                $output,
                $matches,
            );

            self::assertCount(
                1,
                $matches[0],
                'There should be exactly one import for GetViewerQuery with the correct hash',
            );
        } finally {
            // Restore the original content
            file_put_contents($controllerPath, $originalContent);
        }
    }
}
