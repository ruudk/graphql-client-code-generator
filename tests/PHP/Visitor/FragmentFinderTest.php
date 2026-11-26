<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Planner\SchemaSelector;

final class FragmentFinderTest extends TestCase
{
    public function testFindsFragmentOnRegularMethod(): void
    {
        $finder = $this->collect(<<<'PHP'
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
            PHP);

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

    public function testThrowsWhenUsedOnConstructorParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'The #[GeneratedGraphQLFragment] attribute cannot be used on constructor parameters. Found on "App\UserMapper::__construct::$data". Use it on a non-constructor method parameter instead.',
        );

        $this->collect(<<<'PHP'
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
            PHP);
    }

    public function testOnlyFindsFragmentsOfItsSchema(): void
    {
        $finder = $this->collect(<<<'PHP'
            <?php
            namespace App;
            use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLFragment;
            final class UserMapper {
                private const string BILLING = 'billing';
                public function map(
                    #[GeneratedGraphQLFragment('fragment Payer on User { id }', schema: self::BILLING)]
                    object $payer,
                    #[GeneratedGraphQLFragment(fragment: 'fragment Recipient on User { id }', schema: 'shipping')]
                    object $recipient,
                ) : void {}
            }
            PHP, new SchemaSelector('billing', ['billing', 'shipping']));

        self::assertSame(
            [
                'App\UserMapper' => [
                    'map' => [
                        'payer' => 'fragment Payer on User { id }',
                    ],
                ],
            ],
            $finder->fragments,
        );
    }

    private function collect(string $code, SchemaSelector $schemaSelector = new SchemaSelector()): FragmentFinder
    {
        $stmts = new ParserFactory()
            ->createForNewestSupportedVersion()
            ->parse($code);
        self::assertNotNull($stmts);

        $classConstants = new ClassConstantFinder();
        $stmts = new NodeTraverser(new NameResolver(), $classConstants)->traverse($stmts);

        $finder = new FragmentFinder($classConstants->constants, $schemaSelector);
        new NodeTraverser($finder)->traverse($stmts);

        return $finder;
    }
}
