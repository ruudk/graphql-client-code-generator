<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Console;

use Closure;
use Exception;
use GraphQL\Type\Introspection;
use GraphQL\Utils\BuildClientSchema;
use GraphQL\Utils\SchemaPrinter;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Executor\PlanExecutor;
use Ruudk\GraphQLCodeGenerator\GraphQL\IndexByDirectiveSchemaExtender;
use Ruudk\GraphQLCodeGenerator\Planner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
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
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Paths to the configuration file', name: 'config', shortcut: 'c')]
        array $configs = ['graphql-client-code-generator.php'],
        #[Option(description: 'Working directory', name: 'working-dir', shortcut: 'w')]
        ?string $workingDir = null,
        #[Option(description: 'Update the schema by doing an introspection query to the backend.', name: 'update-schema')]
        bool $updateSchema = false,
    ) : int {
        $workingDir ??= getcwd();

        if ($workingDir === false || $workingDir === '') {
            $io->error('Invalid working directory');

            return Command::FAILURE;
        }

        $allConfigs = [];
        foreach ($configs as $configFile) {
            $configPath = $workingDir . '/' . $configFile;

            if ( ! file_exists($configPath)) {
                $io->error(sprintf('Configuration file not found: %s', $configPath));
                $io->note(sprintf('Create a %s file in your project root that returns a %s object or a list of %s objects.', $configFile, Config::class, Config::class));

                return Command::FAILURE;
            }

            try {
                $configData = require $configPath;
            } catch (Throwable $error) {
                $io->error(sprintf('Failed to load configuration from %s: %s', $configFile, $error->getMessage()));

                return Command::FAILURE;
            }

            if ( ! $configData instanceof Config && ! is_array($configData)) {
                $io->error(sprintf('Configuration file %s must return a Config object or an array of Config objects', $configFile));

                return Command::FAILURE;
            }

            // Add configs to our collection
            if ($configData instanceof Config) {
                $allConfigs[] = $configData;
            } else {
                // Validate array items
                foreach ($configData as $index => $configItem) {
                    if ( ! $configItem instanceof Config) {
                        $io->error(sprintf('Invalid configuration at index %d in %s: expected Config object', $index, $configFile));

                        return Command::FAILURE;
                    }

                    $allConfigs[] = $configItem;
                }
            }
        }

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
                    $io->writeln(sprintf('Updating schema for <info>%s</info>... ', $configItem->namespace));

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
                                'sortArguments' => true,
                                'sortEnumValues' => true,
                                'sortFields' => true,
                                'sortInputFields' => true,
                                'sortTypes' => true,
                            ],
                        ),
                    );

                    $io->writeln('✅');
                }
            }

            $io->write(sprintf('Generating code for <info>%s</info>... ', $configItem->namespace));

            try {
                // Planning phase - discovers types
                $plan = new Planner($configItem)->plan();

                // Execution phase - uses discovered types from plan
                $files = new PlanExecutor($configItem)->execute($plan);

                // Clear output directory
                $this->filesystem->remove($configItem->outputDir);

                // Write all files to disk
                foreach ($files as $relativePath => $content) {
                    $fullPath = $configItem->outputDir . '/' . $relativePath;
                    $this->filesystem->dumpFile($fullPath, $content);
                }

                $io->writeln('✅');
            } catch (Throwable $error) {
                $io->writeln('❌');
                $io->error(sprintf('Generation failed: %s', $error->getMessage()));

                if ($io->isVerbose()) {
                    $io->writeln($error->getTraceAsString());
                }

                return Command::FAILURE;
            }
        }

        $io->success('Code generation completed successfully!');

        return Command::SUCCESS;
    }
}
