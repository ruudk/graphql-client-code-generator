<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Console;

use Closure;
use Exception;
use GraphQL\Type\Introspection;
use GraphQL\Utils\BuildClientSchema;
use GraphQL\Utils\SchemaPrinter;
use Ruudk\GraphQLCodeGenerator\Config\ConfigException;
use Ruudk\GraphQLCodeGenerator\Config\ConfigLoader;
use Ruudk\GraphQLCodeGenerator\Executor\PlanExecutor;
use Ruudk\GraphQLCodeGenerator\GraphQL\IndexByDirectiveSchemaExtender;
use Ruudk\GraphQLCodeGenerator\Planner;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Throwable;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

#[AsCommand(
    name: 'generate',
    description: 'Generate GraphQL client code',
)]
final class GenerateCommand
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * @param list<string> $configs
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ConfigException
     * @throws IOException
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Paths to the configuration file', name: 'config', shortcut: 'c')]
        array $configs = ['graphql-client-code-generator.php'],
        #[Option(description: 'Working directory', name: 'working-dir', shortcut: 'w')]
        ?string $workingDir = null,
        #[Option(description: 'Update the schema by doing an introspection query to the backend.', name: 'update-schema')]
        bool $updateSchema = false,
        #[Option(description: 'Guard that the generated files are in sync.', name: 'ensure-sync')]
        bool $ensureSync = false,
    ) : int {
        $workingDir ??= getcwd();

        if ($workingDir === false || $workingDir === '') {
            $io->error('Invalid working directory');

            return Command::FAILURE;
        }

        $allConfigs = ConfigLoader::load(...array_map(
            fn(string $path) => Path::makeAbsolute($path, $workingDir),
            $configs,
        ));

        $exitCode = Command::SUCCESS;

        $io->title('GraphQL Client Code Generator');

        $configCount = count($allConfigs);

        if ($configCount === 0) {
            $io->error('No configurations found');

            return Command::FAILURE;
        }

        foreach ($allConfigs as $index => $configItem) {
            if ($configCount > 1) {
                $io->section(sprintf('Configuration %d of %d', $index + 1, $configCount));
            }

            if ($updateSchema) {
                if ($configItem->introspectionClient === null) {
                    $io->error('Cannot update schema: introspectionClient is not configured');
                } elseif ( ! is_string($configItem->schema)) {
                    $io->error('Cannot update schema: schema should be a string');
                } else {
                    $io->write(sprintf('Updating schema for <info>%s</info>... ', $configItem->namespace));

                    $client = $configItem->introspectionClient;

                    if ($client instanceof Closure) {
                        $client = $client();
                    }

                    Assert::object($client);
                    Assert::methodExists($client, 'graphql');

                    $response = $client->graphql(Introspection::getIntrospectionQuery());

                    Assert::isArray($response);
                    Assert::keyExists($response, 'data');
                    Assert::isArray($response['data']);

                    // @phpstan-ignore argument.type (expects array<string, mixed>, array<mixed, mixed> given)
                    $schema = BuildClientSchema::build($response['data']);

                    if ($configItem->indexByDirective) {
                        $schema = IndexByDirectiveSchemaExtender::extend($schema);
                    }

                    $this->filesystem->dumpFile(
                        $configItem->schema,
                        SchemaPrinter::doPrint(
                            $schema,
                            [
                                'sortEnumValues' => true,
                                'sortFields' => true,
                                'sortTypes' => true,

                                // We might want to do this at some point automatically
                                'sortArguments' => false,
                                'sortInputFields' => false,
                            ],
                        ),
                    );

                    $io->writeln('✅');
                }
            }

            if ( ! $ensureSync) {
                $io->write(sprintf('Generating code for <info>%s</info>... ', $configItem->namespace));
            }

            try {
                // Planning phase - discovers types
                $plan = new Planner($configItem)->plan();

                // Execution phase - uses discovered types from plan
                $files = new PlanExecutor($configItem)->execute($plan);

                if ($ensureSync) {
                    $actual = [];
                    foreach (Finder::create()->files()->in($configItem->outputDir) as $file) {
                        $actual[$file->getPathname()] = $file->getContents();
                    }

                    foreach (array_keys($plan->operationsToInject) as $file) {
                        $actual[$file] = $this->filesystem->readFile($file);
                    }

                    // Sort both arrays by key to ensure consistent comparison
                    ksort($actual);
                    ksort($files);

                    $errors = false;

                    $differ = new Differ(new UnifiedDiffOutputBuilder("--- Actual\n+++ Expected\n"));

                    foreach ($files as $path => $content) {
                        if (isset($actual[$path]) && $content !== $actual[$path]) {
                            $errors = true;
                            $relativePath = Path::makeRelative($path, $configItem->projectDir);
                            $io->writeln(sprintf('<error>%s content does not match expectations</error>', $relativePath));
                            $io->writeln($this->formatDiff($differ->diff($actual[$path], $content)));
                        } elseif ( ! isset($actual[$path])) {
                            $errors = true;
                            $io->writeln(sprintf('<error>%s does not exist</error>', Path::makeRelative($path, $configItem->projectDir)));
                        }
                    }

                    foreach (array_keys($actual) as $path) {
                        if ( ! isset($files[$path])) {
                            $errors = true;
                            $io->writeln(sprintf('%s should not exist', Path::makeRelative($path, $configItem->projectDir)));
                        }
                    }

                    if ($errors) {
                        $io->error('Generated files are not in sync');

                        $exitCode = Command::FAILURE;

                        continue;
                    }

                    $io->success('Generated code is in sync ✅');

                    continue;
                }

                // Clear output directory
                $this->filesystem->remove($configItem->outputDir);

                // Write all files to disk
                foreach ($files as $path => $content) {
                    $this->filesystem->dumpFile($path, $content);
                }

                $io->writeln('✅');
            } catch (Throwable $error) {
                $io->writeln('❌');
                $io->error(sprintf('Generation failed: %s', $error->getMessage()));

                if ($io->isVerbose()) {
                    $io->writeln($error->getTraceAsString());
                }

                $exitCode = Command::FAILURE;
            }
        }

        return $exitCode;
    }

    private function formatDiff(string $diff) : string
    {
        $lines = explode("\n", $diff);

        foreach ($lines as $index => $line) {
            if (str_starts_with($line, '+++') || str_starts_with($line, '---')) {
                $lines[$index] = sprintf('<comment>%s</comment>', OutputFormatter::escape($line));
            } elseif (str_starts_with($line, '@@')) {
                $lines[$index] = sprintf('<fg=cyan>%s</>', OutputFormatter::escape($line));
            } elseif (str_starts_with($line, '+')) {
                $lines[$index] = sprintf('<fg=green>%s</>', OutputFormatter::escape($line));
            } elseif (str_starts_with($line, '-')) {
                $lines[$index] = sprintf('<fg=red>%s</>', OutputFormatter::escape($line));
            } else {
                $lines[$index] = OutputFormatter::escape($line);
            }
        }

        return implode("\n", $lines);
    }
}
