<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class EnsureSyncCommandTest extends TestCase
{
    public function testEnsureSyncSucceedsWhenAllRegisteredHooksAreUsed() : void
    {
        $tester = $this->buildTester();

        $exitCode = $tester->execute([
            '--config' => [__DIR__ . '/Fixtures/with-only-used-hooks.php'],
            '--ensure-sync' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode, $tester->getDisplay());
        self::assertStringContainsString('Generated code is in sync', $tester->getDisplay());
    }

    public function testEnsureSyncFailsWhenHookIsRegisteredButNotUsed() : void
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

    private function buildTester() : CommandTester
    {
        $command = new Command('generate');
        $command->setCode(new GenerateCommand(new Filesystem()));

        return new CommandTester($command);
    }
}
