<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Mago\Linter\Rules\GraphQLHeredocFormatRule;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(GraphQLHeredocFormatRule::class)]
final class GraphQLHeredocFormatRuleTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../GraphQLHeredocFixer/fixtures';
    private string $workspace;

    #[Override]
    protected function setUp() : void
    {
        $this->workspace = sys_get_temp_dir() . '/graphql-heredoc-format-' . bin2hex(random_bytes(4));

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->workspace);
        $filesystem->dumpFile($this->workspace . '/mago.toml', sprintf(<<<'TOML'
            [source]
            paths = ["Example.php"]

            [extension-hosts.graphql]
            command = ["php", %s]
            TOML, json_encode(__DIR__ . '/worker.php', JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));
    }

    #[Override]
    protected function tearDown() : void
    {
        new Filesystem()->remove($this->workspace);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideFixtures() : iterable
    {
        yield 'unformatted nowdoc is formatted' => ['unformatted.php.fixture', 'formatted.php.fixture'];
        yield 'formatted nowdoc is left unchanged' => ['formatted.php.fixture', 'formatted.php.fixture'];
        yield 'invalid GraphQL is left untouched' => ['invalid.php.fixture', 'invalid.php.fixture'];
        yield 'other nowdocs and heredocs are skipped' => ['skipped.php.fixture', 'skipped.php.fixture'];
    }

    #[DataProvider('provideFixtures')]
    public function testFix(string $input, string $expected) : void
    {
        copy(self::FIXTURES . '/' . $input, $this->workspace . '/Example.php');

        $output = [];
        $exitCode = 0;

        exec(
            sprintf(
                '%s --workspace=%s lint --fix --only=%s 2>&1',
                escapeshellarg(dirname(__DIR__, 2) . '/vendor/bin/mago'),
                escapeshellarg($this->workspace),
                escapeshellarg(GraphQLHeredocFormatRule::CODE),
            ),
            $output,
            $exitCode,
        );

        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertStringEqualsFile(
            self::FIXTURES . '/' . $expected,
            (string) file_get_contents($this->workspace . '/Example.php'),
        );
    }
}
