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
use Ruudk\GraphQLCodeGenerator\GraphQL\GroupByDirectiveSchemaExtender;
use Ruudk\GraphQLCodeGenerator\GraphQL\HookDirectiveSchemaExtender;
use Ruudk\GraphQLCodeGenerator\GraphQL\IndexByDirectiveSchemaExtender;
use Ruudk\GraphQLCodeGenerator\GraphQL\OperationArgumentDirectiveSchemaExtender;
use Ruudk\GraphQLCodeGenerator\GraphQL\ThrowWhenNullDirectiveSchemaExtender;
use Ruudk\GraphQLCodeGenerator\Planner;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
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

#[AsCommand(name: 'generate', description: 'Generate GraphQL client code')]
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
        #[Option(
            description: 'Update the schema by doing an introspection query to the backend.',
            name: 'update-schema',
        )]
        bool $updateSchema = false,
        #[Option(description: 'Guard that the generated files are in sync.', name: 'ensure-sync')]
        bool $ensureSync = false,
    ): int {
        $workingDir ??= getcwd();

        if ($workingDir === false || $workingDir === '') {
            $io->error('Invalid working directory');

            return Command::FAILURE;
        }

        $schemas = [];
        foreach (ConfigLoader::load(...array_map(static fn(string $path) => Path::makeAbsolute(
            $path,
            $workingDir,
        ), $configs)) as $config) {
            foreach ($config->schemas as $schemaConfig) {
                $schemas[] = [$config, $schemaConfig];
            }
        }

        $exitCode = Command::SUCCESS;

        $io->title('GraphQL Client Code Generator');

        $schemaCount = count($schemas);

        if ($schemaCount === 0) {
            $io->error('No schemas found');

            return Command::FAILURE;
        }

        foreach ($schemas as $index => [$config, $schemaConfig]) {
            if ($schemaCount > 1) {
                $io->section(
                    $schemaConfig->name === null
                        ? sprintf('Schema %d of %d', $index + 1, $schemaCount)
                        : sprintf('Schema "%s" (%d of %d)', $schemaConfig->name, $index + 1, $schemaCount),
                );
            }

            if ($updateSchema) {
                if ($schemaConfig->introspectionClient === null) {
                    $io->error('Cannot update schema: introspectionClient is not configured');
                } elseif (!is_string($schemaConfig->schema)) {
                    $io->error('Cannot update schema: schema should be a string');
                } else {
                    $io->write(sprintf('Updating schema for <info>%s</info>... ', $schemaConfig->namespace));

                    $client = $schemaConfig->introspectionClient;

                    if ($client instanceof Closure) {
                        $client = $client();
                    }

                    $graphql = [$client, 'graphql'];

                    Assert::isCallable($graphql, 'The introspection client must have a graphql() method');

                    $response = $graphql(Introspection::getIntrospectionQuery());

                    Assert::isArray($response);
                    Assert::keyExists($response, 'data');
                    Assert::isMap($response['data']);

                    $schema = BuildClientSchema::build($response['data']);

                    if ($schemaConfig->indexByDirective) {
                        $schema = IndexByDirectiveSchemaExtender::extend($schema);
                    }

                    if ($schemaConfig->groupByDirective) {
                        $schema = GroupByDirectiveSchemaExtender::extend($schema);
                    }

                    if ($schemaConfig->hooks !== []) {
                        $schema = HookDirectiveSchemaExtender::extend($schema);
                    }

                    if ($schemaConfig->throwWhenNullDirective) {
                        $schema = ThrowWhenNullDirectiveSchemaExtender::extend($schema);
                    }

                    if ($schemaConfig->operationArguments !== []) {
                        $schema = OperationArgumentDirectiveSchemaExtender::extend(
                            $schema,
                            $schemaConfig->operationArguments,
                        );
                    }

                    $this->filesystem->dumpFile($schemaConfig->schema, SchemaPrinter::doPrint($schema, [
                        'sortEnumValues' => true,
                        'sortFields' => true,
                        'sortTypes' => true,

                        // We might want to do this at some point automatically
                        'sortArguments' => false,
                        'sortInputFields' => false,
                    ]));

                    $io->writeln('✅');
                }
            }

            if (!$ensureSync) {
                $io->write(sprintf('Generating code for <info>%s</info>... ', $schemaConfig->namespace));
            }

            try {
                // Planning phase - discovers types
                $plan = new Planner($config, $schemaConfig)->plan();

                // Execution phase - uses discovered types from plan
                $executor = new PlanExecutor($schemaConfig);
                $files = $executor->execute($plan);

                if ($ensureSync) {
                    $actual = [];
                    foreach (Finder::create()->files()->in($schemaConfig->outputDir) as $file) {
                        $actual[$file->getPathname()] = $file->getContents();
                    }

                    foreach (array_keys($plan->operationsToInject) as $file) {
                        $actual[$file] = $this->filesystem->readFile($file);
                    }

                    // Sort both arrays by key to ensure consistent comparison
                    ksort($actual);
                    ksort($files);

                    $errors = false;

                    $differ = new Differ(new StrictUnifiedDiffOutputBuilder([
                        'fromFile' => 'Actual',
                        'toFile' => 'Expected',
                    ]));

                    foreach ($files as $path => $content) {
                        if (isset($actual[$path]) && $content !== $actual[$path]) {
                            $errors = true;
                            $relativePath = Path::makeRelative($path, $config->projectDir);
                            $io->writeln(sprintf(
                                '<error>%s content does not match expectations</error>',
                                $relativePath,
                            ));
                            $io->writeln($this->formatDiff($differ->diff($actual[$path], $content)));
                        } elseif (!isset($actual[$path])) {
                            $errors = true;
                            $io->writeln(sprintf('<error>%s does not exist</error>', Path::makeRelative(
                                $path,
                                $config->projectDir,
                            )));
                        }
                    }

                    foreach (array_keys($actual) as $path) {
                        if (!isset($files[$path])) {
                            $errors = true;
                            $io->writeln(sprintf('%s should not exist', Path::makeRelative(
                                $path,
                                $config->projectDir,
                            )));
                        }
                    }

                    if ($errors) {
                        $io->error('Generated files are not in sync');

                        $exitCode = Command::FAILURE;
                    }

                    $usedHookNames = $executor->hookUsageRegistry->getAllUsedHookNames();
                    $unusedHooks = array_diff_key($schemaConfig->hooks, $usedHookNames);

                    foreach ($unusedHooks as $hookName => $hookDefinition) {
                        $io->warning(sprintf(
                            'Hook "%s" (%s) is registered but is not used by any generated code',
                            $hookName,
                            $hookDefinition->class,
                        ));
                    }

                    foreach ($plan->unusedFragments as $fragmentName => $location) {
                        $io->writeln(sprintf(
                            '<error>Fragment "%s" defined in %s is never used (never spread)</error>',
                            $fragmentName,
                            $location,
                        ));
                    }

                    if ($plan->unusedFragments !== []) {
                        $io->error('Unused fragments found. Remove them or reference them via ...FragmentName.');
                    }

                    if ($unusedHooks !== [] || $plan->unusedFragments !== []) {
                        $exitCode = Command::FAILURE;

                        continue;
                    }

                    if ($errors) {
                        continue;
                    }

                    $io->success('Generated code is in sync ✅');

                    continue;
                }

                // Clear output directory
                $this->filesystem->remove($schemaConfig->outputDir);

                // Write all files to disk
                foreach ($files as $path => $content) {
                    $this->filesystem->dumpFile($path, $content);
                }

                $io->writeln('✅');

                if ($plan->unusedFragments !== []) {
                    $lines = [];
                    foreach ($plan->unusedFragments as $fragmentName => $location) {
                        $lines[] = sprintf('"%s" defined in %s', $fragmentName, $location);
                    }

                    $io->warning(sprintf("The following fragments are never used (never spread). Consider removing them:\n  - %s", implode(
                        "\n  - ",
                        $lines,
                    )));
                }
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

    private function formatDiff(string $diff): string
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
