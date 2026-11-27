<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL;

use Exception;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaExtender;
use InvalidArgumentException;
use JsonException;
use Webmozart\Assert\Assert;

final readonly class GroupByDirectiveSchemaExtender
{
    /**
     * Extends schema with @groupBy directive. If @groupBy already exists, it verifies that it's correct.
     *
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws Exception
     */
    public static function extend(Schema $schema): Schema
    {
        $existing = $schema->getDirective('groupBy');

        if ($existing !== null) {
            Assert::eq($existing->locations, ['FIELD'], 'Expected @groupBy to be on FIELD');
            Assert::count($existing->args, 1, 'Expected @groupBy to have 1 argument');

            [$field] = $existing->args;
            Assert::eq($field->name, 'field', 'Expected @groupBy argument to be named "field"');
            Assert::eq(
                Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                $field->getType(),
                'Expected @groupBy argument to be a non-null list of non-null strings',
            );

            return $schema;
        }

        return SchemaExtender::extend($schema, Parser::parse(<<<'GRAPHQL'
            directive @groupBy(field: [String!]!) on FIELD
            GRAPHQL));
    }
}
