<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class OperationInjectorTest extends TestCase
{
    public function testReplacesSingleObjectContract() : void
    {
        $printed = $this->inject(
            'consume',
            'data',
            'App\Generated\Fragment\UserName56995d\UserName',
            'public function consume(object $data) : void {}',
        );

        self::assertStringContainsString('function consume(UserName $data)', $printed);
    }

    public function testPreservesNullableSingleContract() : void
    {
        $printed = $this->inject(
            'consume',
            'user',
            'App\Generated\Fragment\UserName56995d\UserName',
            'public function consume(?UserName $user) : void {}',
        );

        self::assertStringContainsString('function consume(?UserName $user)', $printed);
    }

    public function testLeavesCollectionTypeForDocblock() : void
    {
        $printed = $this->inject(
            'mapMany',
            'users',
            'App\Generated\Fragment\UserName56995d\UserName',
            'public function mapMany(array $users) : void {}',
        );

        self::assertStringContainsString('function mapMany(array $users)', $printed);
    }

    private function inject(string $method, string $param, string $fqcn, string $methodCode) : string
    {
        $code = sprintf('<?php class C { %s }', $methodCode);

        $stmts = new ParserFactory()->createForNewestSupportedVersion()->parse($code);
        self::assertNotNull($stmts);

        $stmts = new NodeTraverser(
            new OperationInjector([
                $method => [
                    $param => $fqcn,
                ],
            ]),
        )->traverse($stmts);

        return new Standard()->prettyPrintFile($stmts);
    }
}
