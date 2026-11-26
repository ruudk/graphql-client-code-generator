<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use Symfony\Component\Filesystem\Path;

/**
 * The configuration of a codebase: where it lives, which directories every
 * schema reads, and the schemas to generate code for.
 *
 * ```php
 * return Config::create(projectDir: __DIR__)
 *     ->withInlineProcessingDirectory(__DIR__ . '/src')
 *     ->withSchema(SchemaConfig::create(...));
 * ```
 */
final readonly class Config
{
    /**
     * @param list<string> $inlineProcessingDirectories
     * @param list<string> $twigProcessingDirectories
     * @param list<SchemaConfig> $schemas
     */
    private function __construct(
        public string $projectDir,
        public array $inlineProcessingDirectories = [],
        public array $twigProcessingDirectories = [],
        public array $schemas = [],
    ) {}

    public static function create(string $projectDir): self
    {
        return new self(projectDir: $projectDir);
    }

    /**
     * Directories that every schema reads for `#[GeneratedGraphQLClient]` and
     * `#[GeneratedGraphQLFragment]` attributes. Use
     * `SchemaConfig::withInlineProcessingDirectory()` for a directory that only
     * one schema reads.
     */
    public function withInlineProcessingDirectory(string $directory, string ...$directories): self
    {
        return clone($this, [
            'inlineProcessingDirectories' => array_merge(
                $this->inlineProcessingDirectories,
                [$directory],
                array_values($directories),
            ),
        ]);
    }

    /**
     * Directories that every schema reads for `{% graphql %}` blocks. Use
     * `SchemaConfig::withTwigProcessingDirectory()` for a directory that only
     * one schema reads.
     */
    public function withTwigProcessingDirectory(string $directory, string ...$directories): self
    {
        return clone($this, [
            'twigProcessingDirectories' => array_merge(
                $this->twigProcessingDirectories,
                [$directory],
                array_values($directories),
            ),
        ]);
    }

    /**
     * @throws ConfigException
     */
    public function withSchema(SchemaConfig $schema): self
    {
        $schemas = [...$this->schemas, $schema];

        if (count($schemas) > 1) {
            foreach ($schemas as $existing) {
                if ($existing->name === null) {
                    throw new ConfigException(sprintf(
                        'The schema that generates into "%s" has no name. Give every schema a name with SchemaConfig::create(name: ...) when a config has more than one.',
                        $existing->namespace,
                    ));
                }
            }
        }

        foreach ($this->schemas as $existing) {
            if ($schema->name !== null && $existing->name === $schema->name) {
                throw new ConfigException(sprintf('There is more than one schema named "%s".', $schema->name));
            }

            if ($existing->namespace === $schema->namespace) {
                throw new ConfigException(sprintf(
                    'There is more than one schema that generates into the namespace "%s".',
                    $schema->namespace,
                ));
            }

            if (Path::canonicalize($existing->outputDir) === Path::canonicalize($schema->outputDir)) {
                throw new ConfigException(sprintf(
                    'There is more than one schema that generates into the directory "%s".',
                    $schema->outputDir,
                ));
            }
        }

        return clone($this, [
            'schemas' => $schemas,
        ]);
    }

    /**
     * The directories the schema reads for inline attributes: the ones of this
     * config and the ones of the schema itself.
     *
     * @return list<string>
     */
    public function inlineProcessingDirectoriesOf(SchemaConfig $schema): array
    {
        return [...$this->inlineProcessingDirectories, ...$schema->inlineProcessingDirectories];
    }

    /**
     * The directories the schema reads for Twig templates: the ones of this
     * config and the ones of the schema itself.
     *
     * @return list<string>
     */
    public function twigProcessingDirectoriesOf(SchemaConfig $schema): array
    {
        return [...$this->twigProcessingDirectories, ...$schema->twigProcessingDirectories];
    }
}
