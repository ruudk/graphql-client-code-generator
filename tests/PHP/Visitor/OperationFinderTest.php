<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperationFinderTest extends TestCase
{
    private const array FOUND = [
        'App\SomeController' => [
            '__construct' => [
                'query' => 'query Viewer { viewer }',
            ],
        ],
    ];

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function provideMatchingScopes() : iterable
    {
        yield 'no scope on either side' => ['self::OPERATION', null];
        yield 'an explicit null' => ['self::OPERATION, null', null];
        yield 'a string by position' => ["self::OPERATION, 'billing'", 'billing'];
        yield 'a string by name' => ["operation: self::OPERATION, scope: 'billing'", 'billing'];
        yield 'named arguments in another order' => ["scope: 'billing', operation: self::OPERATION", 'billing'];
    }

    #[DataProvider('provideMatchingScopes')]
    public function testFindsAnOperationOfItsScope(string $arguments, ?string $scope) : void
    {
        $finder = $this->collect($arguments, $scope);

        self::assertSame([self::FOUND, []], [$finder->operations, $finder->skipped]);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string}>
     */
    public static function provideOtherScopes() : iterable
    {
        yield 'a scoped operation without a scope' => ["self::OPERATION, 'billing'", null, 'billing'];
        yield 'an operation without a scope with one' => ['self::OPERATION', 'billing', null];
        yield 'another string' => ["self::OPERATION, 'shipping'", 'billing', 'shipping'];
        yield 'another casing' => ["self::OPERATION, 'Billing'", 'billing', 'Billing'];
    }

    #[DataProvider('provideOtherScopes')]
    public function testSkipsAnOperationOfAnotherScope(string $arguments, ?string $scope, ?string $skipped) : void
    {
        $finder = $this->collect($arguments, $scope);

        self::assertSame(
            [[], [
                'App\SomeController::__construct::$query' => $skipped,
            ]],
            [$finder->operations, $finder->skipped],
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUnreadableScopes() : iterable
    {
        yield 'a constant of another class' => ['self::OPERATION, Scopes::BILLING'];
        yield 'an enum case' => ['self::OPERATION, Scope::Billing'];
        yield 'a constant of the same class' => ['self::OPERATION, self::SCOPE'];
        yield 'an integer' => ['self::OPERATION, 1'];
        yield 'a concatenation' => ["self::OPERATION, 'bill' . 'ing'"];
    }

    #[DataProvider('provideUnreadableScopes')]
    public function testThrowsForAScopeItCanNotRead(string $arguments) : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Cannot read the scope of #[GeneratedGraphQLClient] on "App\SomeController::__construct::$query". Write it as a string, like \'billing\'.');

        $this->collect($arguments, 'billing');
    }

    private function collect(string $arguments, ?string $scope) : OperationFinder
    {
        $stmts = new ParserFactory()->createForNewestSupportedVersion()->parse(sprintf(
            <<<'PHP'
                <?php
                namespace App;
                use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
                final class SomeController {
                    private const string OPERATION = 'query Viewer { viewer }';
                    private const string SCOPE = 'billing';
                    public function __construct(
                        #[GeneratedGraphQLClient(%s)]
                        private object $query,
                    ) {}
                }
                PHP,
            $arguments,
        ));
        self::assertNotNull($stmts);

        $classConstants = new ClassConstantFinder();
        $stmts = new NodeTraverser(new NameResolver(), $classConstants)->traverse($stmts);

        $finder = new OperationFinder($classConstants->constants, $scope);
        new NodeTraverser($finder)->traverse($stmts);

        return $finder;
    }
}
