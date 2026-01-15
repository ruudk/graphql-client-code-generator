<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\StaleImportRemoval;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\PHP\Visitor\StaleImportRemover;

final class StaleImportRemoverTest extends TestCase
{
    public function testPatternMatching() : void
    {
        $code = <<<'PHP'
            <?php

            namespace App;

            use App\Generated\Query\GetViewerabc123\GetViewerQuery;
            use App\Generated\Query\GetViewerdef456\GetViewerQuery as AliasedQuery;
            use App\Generated\Mutation\CreateUser123abc\CreateUserMutation;
            use SomeOther\Class\NotGenerated;

            class Foo {}
            PHP;

        $parser = new ParserFactory()->createForNewestSupportedVersion();
        $stmts = $parser->parse($code);

        self::assertNotNull($stmts);

        $remover = new StaleImportRemover(
            'App\Generated',
            ['App\Generated\Query\GetViewer6b9a6d\GetViewerQuery'], // Only this one is valid
        );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($remover);
        $newStmts = $traverser->traverse($stmts);

        $printer = new Standard();
        $output = $printer->prettyPrintFile($newStmts);

        // The valid import should be... wait, it's not in the original code
        // Let me check what happens to stale imports
        self::assertStringNotContainsString('GetViewerabc123', $output, 'Stale abc123 should be removed');
        self::assertStringNotContainsString('GetViewerdef456', $output, 'Stale def456 should be removed');
        self::assertStringNotContainsString('CreateUser123abc', $output, 'Stale mutation should be removed');
        self::assertStringContainsString('SomeOther\Class\NotGenerated', $output, 'Non-generated import should remain');
    }

    public function testRegexPatternDirectly() : void
    {
        $namespace = 'App\Generated';
        $escapedNamespace = preg_quote($namespace, '/');
        $pattern = '/^' . $escapedNamespace . '\\\\(?:Query|Mutation)\\\\[A-Za-z]+[a-f0-9]{6}\\\\/';

        // Should match stale imports
        self::assertSame(1, preg_match($pattern, 'App\Generated\Query\GetViewerabc123\GetViewerQuery'));
        self::assertSame(1, preg_match($pattern, 'App\Generated\Query\GetViewer6b9a6d\GetViewerQuery'));
        self::assertSame(1, preg_match($pattern, 'App\Generated\Mutation\CreateUser123abc\CreateUserMutation'));

        // Should NOT match non-generated imports
        self::assertSame(0, preg_match($pattern, 'SomeOther\Class\NotGenerated'));
        self::assertSame(0, preg_match($pattern, 'App\Generated\Enum\SomeEnum'));
    }

    public function testExactIntegrationScenario() : void
    {
        // This matches the exact scenario from the integration test
        $namespace = 'Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated';
        $escapedNamespace = preg_quote($namespace, '/');
        $pattern = '/^' . $escapedNamespace . '\\\\(?:Query|Mutation)\\\\[A-Za-z]+[a-f0-9]{6}\\\\/';

        // The stale import
        $stale = 'Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewerabc123\GetViewerQuery';

        // The correct import
        $correct = 'Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewer6b9a6d\GetViewerQuery';

        self::assertSame(1, preg_match($pattern, $stale), 'Stale import should match pattern');
        self::assertSame(1, preg_match($pattern, $correct), 'Correct import should match pattern');
    }

    public function testRemovalWithMultipleVisitors() : void
    {
        // Simulate what happens in PlanExecutor
        $code = <<<'PHP'
            <?php

            namespace Ruudk\GraphQLCodeGenerator\StaleImportRemoval;

            use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
            use Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewerabc123\GetViewerQuery;
            use Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewerdef456\GetViewerQuery as AliasedQuery;

            final readonly class ControllerWithStaleImport {}
            PHP;

        $parser = new ParserFactory()->createForNewestSupportedVersion();
        $stmts = $parser->parse($code);

        self::assertNotNull($stmts);

        $fqcns = ['Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewer6b9a6d\GetViewerQuery'];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new \PhpParser\NodeVisitor\NodeConnectingVisitor());
        $traverser->addVisitor(new StaleImportRemover('Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated', $fqcns));
        $traverser->addVisitor(new \Ruudk\GraphQLCodeGenerator\PHP\Visitor\UseStatementInserter($fqcns));
        $newStmts = $traverser->traverse($stmts);

        $printer = new Standard();
        $output = $printer->prettyPrintFile($newStmts);

        self::assertStringNotContainsString('GetViewerabc123', $output, 'Stale abc123 should be removed');
        self::assertStringNotContainsString('GetViewerdef456', $output, 'Stale def456 should be removed');
        self::assertStringContainsString('GetViewer6b9a6d', $output, 'Correct import should be present');
    }
}
