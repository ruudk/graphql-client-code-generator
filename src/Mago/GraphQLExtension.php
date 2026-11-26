<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago;

use Composer\InstalledVersions;
use Mago\Sdk\Extension;
use Ruudk\GraphQLCodeGenerator\Config\ConfigException;
use Ruudk\GraphQLCodeGenerator\Config\ConfigLoader;
use Ruudk\GraphQLCodeGenerator\Mago\Analyzer\RestrictedUsagePlugin;
use Ruudk\GraphQLCodeGenerator\Mago\Linter\Rules\GraphQLHeredocFormatRule;

/**
 * Mago extension for projects that use the GraphQL client code generator.
 *
 * It contributes the `graphql/heredoc-format` linter rule and, when the
 * generator configs are passed, the `graphql` analyzer plugin that restricts
 * usage of generated classes to the code they were generated for.
 *
 * Start it from a worker entrypoint that your project owns:
 *
 *     (new Mago\Sdk\Worker(GraphQLExtension::create([__DIR__ . '/../graphql.php'])))->run();
 *
 * @api
 */
final class GraphQLExtension
{
    private function __construct() {}

    /**
     * @param list<string> $configs paths to the code generator config files, used to find the generated namespaces
     * @throws ConfigException
     */
    public static function create(array $configs = []): Extension
    {
        $analyzerPlugins = [];

        if ($configs !== []) {
            $namespaces = [];
            foreach (ConfigLoader::load(...$configs) as $config) {
                foreach ($config->schemas as $schemaConfig) {
                    $namespaces[] = $schemaConfig->namespace;
                }
            }

            $analyzerPlugins[] = new RestrictedUsagePlugin(array_values(array_unique($namespaces)));
        }

        return new Extension(
            identifier: 'ruudk/graphql-client-code-generator',
            name: 'GraphQL client code generator',
            version: InstalledVersions::getPrettyVersion('ruudk/graphql-client-code-generator') ?? 'dev',
            linterRules: [new GraphQLHeredocFormatRule()],
            analyzerPlugins: $analyzerPlugins,
        );
    }
}
