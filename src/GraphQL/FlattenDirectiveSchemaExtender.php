<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL;

use Exception;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaExtender;
use InvalidArgumentException;
use JsonException;
use Webmozart\Assert\Assert;

final readonly class FlattenDirectiveSchemaExtender
{
    /**
     * Extends schema with @flatten directive. If @flatten already exists, it verifies that it's correct.
     *
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws Exception
     */
    public static function extend(Schema $schema): Schema
    {
        $existing = $schema->getDirective('flatten');

        if ($existing !== null) {
            Assert::eq($existing->locations, ['FIELD'], 'Expected @flatten to be on FIELD');
            Assert::count($existing->args, 0, 'Expected @flatten to have 0 arguments');

            return $schema;
        }

        return SchemaExtender::extend($schema, Parser::parse(<<<'GRAPHQL'
            directive @flatten on FIELD
            GRAPHQL));
    }
}
