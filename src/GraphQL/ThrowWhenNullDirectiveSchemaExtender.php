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

final readonly class ThrowWhenNullDirectiveSchemaExtender
{
    /**
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws Exception
     */
    public static function extend(Schema $schema) : Schema
    {
        $existing = $schema->getDirective('throwWhenNull');

        if ($existing !== null) {
            Assert::eq($existing->locations, ['FIELD'], 'Expected @throwWhenNull to be on FIELD');
            Assert::count($existing->args, 0, 'Expected @throwWhenNull to have 0 arguments');

            return $schema;
        }

        return SchemaExtender::extend(
            $schema,
            Parser::parse(
                <<<'GRAPHQL'
                    directive @throwWhenNull on FIELD
                    GRAPHQL,
            ),
        );
    }
}
