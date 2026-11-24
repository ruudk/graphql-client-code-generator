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

    public function testEnsureSyncSucceedsWhenEveryScopeHasAConfig() : void
    {
        $tester = $this->buildTester();

        $exitCode = $tester->execute([
            '--config' => [__DIR__ . '/Fixtures/with-scopes.php'],
            '--ensure-sync' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode, $tester->getDisplay());
    }

    public function testEnsureSyncFailsWhenNoConfigHasTheScopeOfAnAttribute() : void
    {
        $tester = $this->buildTester();

        $exitCode = $tester->execute([
            '--config' => [__DIR__ . '/Fixtures/with-scope-no-config-has.php'],
            '--ensure-sync' => true,
        ]);

        self::assertSame(Command::FAILURE, $exitCode, $tester->getDisplay());
        self::assertStringContainsString(
            'Ruudk\GraphQLCodeGenerator\Scope\CheckoutController::__construct::$parcels has the scope "shipping", which no config that reads it has',
            $tester->getDisplay(),
        );
    }

    private function buildTester() : CommandTester
    {
        $command = new Command('generate');
        $command->setCode(new GenerateCommand(new Filesystem()));

        return new CommandTester($command);
    }
}
