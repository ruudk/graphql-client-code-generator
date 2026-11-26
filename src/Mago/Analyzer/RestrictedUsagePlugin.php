<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago\Analyzer;

use Mago\Sdk\Analyzer\Plugin;
use Mago\Sdk\Analyzer\PluginDefinition;
use Mago\Sdk\Analyzer\PluginRegistry;
use Override;
use Ruudk\GraphQLCodeGenerator\Mago\Analyzer\Hooks\RestrictedUsageHook;

/**
 * Restricts usage of generated classes marked `#[Generated(restricted: true)]`
 * to the class or template they were generated for.
 *
 * @api
 */
final readonly class RestrictedUsagePlugin implements Plugin
{
    public const string IDENTIFIER = 'graphql';

    /**
     * @param list<string> $namespaces namespaces that hold generated code
     */
    public function __construct(
        private array $namespaces,
    ) {}

    #[Override]
    public function getDefinition(): PluginDefinition
    {
        return new PluginDefinition(
            identifier: self::IDENTIFIER,
            name: 'GraphQL client code generator',
            description: 'Restricts usage of generated GraphQL classes to the code they were generated for.',
        );
    }

    #[Override]
    public function register(PluginRegistry $registry): void
    {
        $registry->registerAfterFileAnalysisHook(new RestrictedUsageHook($this->namespaces));
    }
}
