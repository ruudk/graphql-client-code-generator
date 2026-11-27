<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan;

use Ruudk\GraphQLCodeGenerator\Config\ConfigException;
use Ruudk\GraphQLCodeGenerator\Config\ConfigLoader;

/**
 * The namespaces that the configured schemas generate their classes in.
 */
final readonly class GeneratedNamespaces
{
    /**
     * @param list<string> $namespaces
     */
    private function __construct(
        private array $namespaces,
    ) {}

    /**
     * @param list<string> $configs
     * @throws ConfigException
     */
    public static function fromConfigs(array $configs): self
    {
        $namespaces = [];
        foreach (ConfigLoader::load(...$configs) as $config) {
            foreach ($config->schemas as $schemaConfig) {
                $namespaces[] = $schemaConfig->namespace;
            }
        }

        return new self($namespaces);
    }

    public function contains(string $className): bool
    {
        return array_any($this->namespaces, static fn($namespace) => str_starts_with($className, $namespace));
    }
}
