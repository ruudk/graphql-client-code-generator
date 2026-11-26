<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\Assert;

final class AttributeArgumentsTest extends TestCase
{
    public function testReadsAPositionalArgument(): void
    {
        self::assertSame('billing', $this->readSchema("#[Client('query', 'billing')]"));
    }

    public function testReadsANamedArgument(): void
    {
        self::assertSame('billing', $this->readSchema("#[Client(schema: 'billing', operation: 'query')]"));
    }

    public function testReadsAConstantOfTheSameClass(): void
    {
        self::assertSame('billing', $this->readSchema('#[Client(self::OPERATION, schema: self::SCHEMA)]'));
        self::assertSame('billing', $this->readSchema('#[Client(self::OPERATION, static::SCHEMA)]'));
    }

    public function testReadsAMissingOrNullArgumentAsNull(): void
    {
        self::assertNull($this->readSchema("#[Client('query')]"));
        self::assertNull($this->readSchema("#[Client('query', schema: null)]"));
    }

    public function testCannotReadAConstantOfAnotherClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'Cannot read the "schema" argument of #[Client] on "App\Controller::__construct::$query". Write it as a string, like \'billing\', or as a string constant of the same class, like self::SCHEMA.',
        );

        $this->readSchema('#[Client(self::OPERATION, schema: Schemas::BILLING)]');
    }

    private function readSchema(string $attribute): ?string
    {
        $stmts = new ParserFactory()
            ->createForNewestSupportedVersion()
            ->parse(sprintf(<<<'PHP'
                <?php
                namespace App;
                final class Controller {
                    private const string SCHEMA = 'billing';
                    public function __construct(
                        %s
                        public object $query,
                    ) {}
                }
                PHP, $attribute));
        Assert::notNull($stmts);

        $constants = new ClassConstantFinder();
        $stmts = new NodeTraverser(new NameResolver(), $constants)->traverse($stmts);

        $node = new NodeFinder()->findFirstInstanceOf($stmts, Node\Attribute::class);
        Assert::isInstanceOf($node, Node\Attribute::class);

        return AttributeArguments::string(
            $node,
            'schema',
            1,
            $constants->constants,
            'App\Controller::__construct::$query',
        );
    }
}
