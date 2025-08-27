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

final readonly class IndexByDirectiveSchemaExtender
{
    /**
     * Extends schema with @indexBy directive. If @indexBy already exists, it verifies that it's correct.
     *
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws Exception
     */
    public static function extend(Schema $schema) : Schema
    {
        $existing = $schema->getDirective('indexBy');

        if ($existing !== null) {
            Assert::eq($existing->locations, ['FIELD'], 'Expected @indexBy to be on FIELD');
            Assert::count($existing->args, 1, 'Expected @indexBy to have 1 argument');

            [$field] = $existing->args;
            Assert::eq($field->name, 'field', 'Expected @indexBy argument to be named "field"');
            Assert::eq(Type::nonNull(Type::string()), $field->getType(), 'Expected @indexBy argument to be a non-null string');

            return $schema;
        }

        return SchemaExtender::extend(
            $schema,
            Parser::parse(
                <<<'GRAPHQL'
                    directive @indexBy(field: String!) on FIELD
                    GRAPHQL,
            ),
        );
    }
}
