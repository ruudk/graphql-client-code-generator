<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class FragmentFinderTest extends TestCase
{
    public function testFindsFragmentOnRegularMethod() : void
    {
        $finder = $this->collect(
            <<<'PHP'
                <?php
                namespace App;
                use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLFragment;
                final class UserMapper {
                    private const string FRAGMENT = 'fragment UserName on User { id }';
                    public function mapMany(
                        #[GeneratedGraphQLFragment(self::FRAGMENT)]
                        array $users,
                    ) : void {}
                }
                PHP,
        );

        self::assertSame(
            [
                'App\UserMapper' => [
                    'mapMany' => [
                        'users' => 'fragment UserName on User { id }',
                    ],
                ],
            ],
            $finder->fragments,
        );
    }

    public function testFindsFragmentOfItsScope() : void
    {
        self::assertSame(
            [
                'App\UserMapper' => [
                    'mapMany' => [
                        'users' => 'fragment UserName on User { id }',
                    ],
                ],
            ],
            $this->collectScoped('billing', "fragment: self::FRAGMENT, scope: 'billing'")->fragments,
        );
    }

    public function testSkipsFragmentOfAnotherScope() : void
    {
        $finder = $this->collectScoped('billing', "self::FRAGMENT, 'shipping'");

        self::assertSame(
            [[], [
                'App\UserMapper::mapMany::$users' => 'shipping',
            ]],
            [$finder->fragments, $finder->skipped],
        );
    }

    public function testThrowsForAScopeItCanNotRead() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Cannot read the scope of #[GeneratedGraphQLFragment] on "App\UserMapper::mapMany::$users". Write it as a string, like \'billing\'.');

        $this->collectScoped('billing', 'self::FRAGMENT, Scopes::BILLING');
    }

    public function testSkipsScopedFragmentWithoutAScope() : void
    {
        self::assertSame([], $this->collectScoped(null, "self::FRAGMENT, 'billing'")->fragments);
    }

    public function testSkipsFragmentWithoutAScopeWithOne() : void
    {
        self::assertSame([], $this->collectScoped('billing', 'self::FRAGMENT')->fragments);
    }

    public function testThrowsWhenUsedOnConstructorParameter() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The #[GeneratedGraphQLFragment] attribute cannot be used on constructor parameters. Found on "App\UserMapper::__construct::$data". Use it on a non-constructor method parameter instead.');

        $this->collect(
            <<<'PHP'
                <?php
                namespace App;
                use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLFragment;
                final class UserMapper {
                    private const string FRAGMENT = 'fragment UserName on User { id }';
                    public function __construct(
                        #[GeneratedGraphQLFragment(self::FRAGMENT)]
                        private object $data,
                    ) {}
                }
                PHP,
        );
    }

    private function collectScoped(?string $scope, string $arguments) : FragmentFinder
    {
        return $this->collect(
            sprintf(
                <<<'PHP'
                    <?php
                    namespace App;
                    use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLFragment;
                    final class UserMapper {
                        private const string FRAGMENT = 'fragment UserName on User { id }';
                        public function mapMany(
                            #[GeneratedGraphQLFragment(%s)]
                            array $users,
                        ) : void {}
                    }
                    PHP,
                $arguments,
            ),
            $scope,
        );
    }

    private function collect(string $code, ?string $scope = null) : FragmentFinder
    {
        $stmts = new ParserFactory()->createForNewestSupportedVersion()->parse($code);
        self::assertNotNull($stmts);

        $classConstants = new ClassConstantFinder();
        $stmts = new NodeTraverser(new NameResolver(), $classConstants)->traverse($stmts);

        $finder = new FragmentFinder($classConstants->constants, $scope);
        new NodeTraverser($finder)->traverse($stmts);

        return $finder;
    }
}
