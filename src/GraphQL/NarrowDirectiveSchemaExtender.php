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

final readonly class NarrowDirectiveSchemaExtender
{
    /**
     * Extends schema with @narrow directive. If @narrow already exists, it verifies that it's correct.
     *
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws Exception
     */
    public static function extend(Schema $schema): Schema
    {
        $existing = $schema->getDirective('narrow');

        if ($existing !== null) {
            Assert::eq($existing->locations, ['INLINE_FRAGMENT'], 'Expected @narrow to be on INLINE_FRAGMENT');
            Assert::count($existing->args, 0, 'Expected @narrow to have 0 arguments');

            return $schema;
        }

        return SchemaExtender::extend($schema, Parser::parse(<<<'GRAPHQL'
            directive @narrow on INLINE_FRAGMENT
            GRAPHQL));
    }
}
