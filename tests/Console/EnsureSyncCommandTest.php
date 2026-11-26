<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class EnsureSyncCommandTest extends TestCase
{
    public function testEnsureSyncSucceedsWhenAllRegisteredHooksAreUsed(): void
    {
        $tester = $this->buildTester();

        $exitCode = $tester->execute([
            '--config' => [__DIR__ . '/Fixtures/with-only-used-hooks.php'],
            '--ensure-sync' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode, $tester->getDisplay());
        self::assertStringContainsString('Generated code is in sync', $tester->getDisplay());
    }

    public function testEnsureSyncFailsWhenHookIsRegisteredButNotUsed(): void
    {
        $tester = $this->buildTester();

        $exitCode = $tester->execute([
            '--config' => [__DIR__ . '/Fixtures/with-unused-hook.php'],
            '--ensure-sync' => true,
        ]);

        self::assertSame(Command::FAILURE, $exitCode, $tester->getDisplay());

        $output = $tester->getDisplay();
        self::assertStringContainsString('unusedHookForTesting', $output);
        self::assertStringContainsString('not used', $output);
    }

    public function testEnsureSyncSucceedsForSchemasThatShareADirectory(): void
    {
        $tester = $this->buildTester();

        $exitCode = $tester->execute([
            '--config' => [__DIR__ . '/Fixtures/with-schemas.php'],
            '--ensure-sync' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode, $tester->getDisplay());
        self::assertStringContainsString('Schema "billing" (1 of 2)', $tester->getDisplay());
        self::assertStringContainsString('Schema "shipping" (2 of 2)', $tester->getDisplay());
    }

    public function testEnsureSyncFailsWhenASharedFileDoesNotNameItsSchema(): void
    {
        $tester = $this->buildTester();

        $exitCode = $tester->execute([
            '--config' => [__DIR__ . '/Fixtures/with-schema-not-named.php'],
            '--ensure-sync' => true,
        ]);

        self::assertSame(Command::FAILURE, $exitCode, $tester->getDisplay());
        self::assertStringContainsString(
            'has to name its schema, because the schemas "billing", "shipping" read it.',
            (string) preg_replace('/\s+/', ' ', $tester->getDisplay()),
        );
    }

    private function buildTester(): CommandTester
    {
        $command = new Command('generate');
        $command->setCode(new GenerateCommand(new Filesystem()));

        return new CommandTester($command);
    }
}
