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

    public function testThrowsWhenUsedOnConstructorParameter() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The #[GeneratedGraphQLFragment] attribute cannot be used on constructor parameters. Found on "App\UserMapper::__construct::$data". Use it on a non-constructor method parameter instead.');

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

    private function collect(string $code) : FragmentFinder
    {
        $stmts = new ParserFactory()->createForNewestSupportedVersion()->parse($code);
        self::assertNotNull($stmts);

        $classConstants = new ClassConstantFinder();
        $stmts = new NodeTraverser(new NameResolver(), $classConstants)->traverse($stmts);

        $finder = new FragmentFinder($classConstants->constants);
        new NodeTraverser($finder)->traverse($stmts);

        return $finder;
    }
}
