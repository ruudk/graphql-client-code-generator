<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Planner\SchemaSelector;

final class OperationFinderTest extends TestCase
{
    private const string CONTROLLER = <<<'PHP'
        <?php
        namespace App;
        use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
        final class CheckoutController {
            private const string BILLING = 'billing';
            private const string VIEWER = 'query Viewer { viewer { login } }';
            public function __construct(
                #[GeneratedGraphQLClient(self::VIEWER, self::BILLING)]
                public object $viewer,
                #[GeneratedGraphQLClient(operation: 'query Parcels { parcels { id } }', schema: 'shipping')]
                public object $parcels,
            ) {}
        }
        PHP;

    public function testFindsOperationWithoutSchema(): void
    {
        $finder = $this->collect(<<<'PHP'
            <?php
            namespace App;
            use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
            final class ViewerController {
                public function __construct(
                    #[GeneratedGraphQLClient('query Viewer { viewer { login } }')]
                    public object $viewer,
                ) {}
            }
            PHP, new SchemaSelector());

        self::assertSame(
            [
                'App\ViewerController' => [
                    '__construct' => [
                        'viewer' => 'query Viewer { viewer { login } }',
                    ],
                ],
            ],
            $finder->operations,
        );
    }

    public function testOnlyFindsOperationsOfItsSchema(): void
    {
        self::assertSame(
            [
                'App\CheckoutController' => [
                    '__construct' => [
                        'viewer' => 'query Viewer { viewer { login } }',
                    ],
                ],
            ],
            $this->collect(self::CONTROLLER, new SchemaSelector('billing', ['billing', 'shipping']))->operations,
        );
        self::assertSame(
            [
                'App\CheckoutController' => [
                    '__construct' => [
                        'parcels' => 'query Parcels { parcels { id } }',
                    ],
                ],
            ],
            $this->collect(self::CONTROLLER, new SchemaSelector('shipping', ['billing', 'shipping']))->operations,
        );
    }

    public function testThrowsWhenTheSchemaIsUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'App\CheckoutController::__construct::$parcels names the schema "shipping", but only the schemas "billing", "payments" read it.',
        );

        $this->collect(self::CONTROLLER, new SchemaSelector('billing', ['billing', 'payments']));
    }

    private function collect(string $code, SchemaSelector $schemaSelector): OperationFinder
    {
        $stmts = new ParserFactory()
            ->createForNewestSupportedVersion()
            ->parse($code);
        self::assertNotNull($stmts);

        $classConstants = new ClassConstantFinder();
        $stmts = new NodeTraverser(new NameResolver(), $classConstants)->traverse($stmts);

        $finder = new OperationFinder($classConstants->constants, $schemaSelector);
        new NodeTraverser($finder)->traverse($stmts);

        return $finder;
    }
}
