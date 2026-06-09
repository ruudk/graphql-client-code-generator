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

final readonly class HookDirectiveSchemaExtender
{
    /**
     * Extends schema with @hook directive. If @hook already exists, it verifies that it's correct.
     *
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws Exception
     */
    public static function extend(Schema $schema) : Schema
    {
        $existing = $schema->getDirective('hook');

        if ($existing !== null) {
            Assert::eq($existing->locations, ['FIELD'], 'Expected @hook to be on FIELD');
            Assert::count($existing->args, 1, 'Expected @hook to have 1 argument');

            [$name] = $existing->args;
            Assert::eq($name->name, 'name', 'Expected @hook first argument to be named "name"');
            Assert::eq(Type::nonNull(Type::string()), $name->getType(), 'Expected @hook "name" argument to be a non-null string');

            return $schema;
        }

        return SchemaExtender::extend(
            $schema,
            Parser::parse(
                <<<'GRAPHQL'
                    directive @hook(name: String!) on FIELD
                    GRAPHQL,
            ),
        );
    }
}
