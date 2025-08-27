<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Exception;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildClientSchema;
use GraphQL\Utils\BuildSchema;
use JsonException;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Assert\Assert;

final class SchemaLoader
{
    public private(set) ?string $schemaPath = null;

    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws Exception
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     * @throws \Webmozart\Assert\InvalidArgumentException
     */
    public function load(Schema | string $schema, bool $indexByDirective) : Schema
    {
        if (is_string($schema) && str_ends_with($schema, '.graphql')) {
            $this->schemaPath = $schema;
            $schema = BuildSchema::build($this->filesystem->readFile($schema));
        } elseif (is_string($schema) && str_ends_with($schema, '.json')) {
            $this->schemaPath = $schema;
            $introspection = json_decode($this->filesystem->readFile($schema), true, flags: JSON_THROW_ON_ERROR);

            Assert::isArray($introspection, 'Expected introspection to be an array');
            Assert::keyExists($introspection, 'data', 'Expected introspection to have a "data" key');
            Assert::isArray($introspection['data'], 'Expected introspection data to be an array');

            // @phpstan-ignore argument.type (expects array<string, mixed>, array<mixed, mixed> given)
            $schema = BuildClientSchema::build($introspection['data']);
        }

        Assert::isInstanceOf($schema, Schema::class, 'Invalid schema given, expected .graphql or .json file or Schema instance');

        if ($indexByDirective) {
            $schema = GraphQL\IndexByDirectiveSchemaExtender::extend($schema);
        }

        return $schema;
    }
}
