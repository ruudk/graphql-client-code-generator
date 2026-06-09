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
use Ruudk\GraphQLCodeGenerator\Config\OperationArgument;

final readonly class OperationArgumentDirectiveSchemaExtender
{
    /**
     * Registers a directive (on QUERY and/or MUTATION) for every distinct `directive`
     * referenced by the configured operation arguments that is not already defined in the
     * schema. Directives that already exist are left untouched — they belong to the user's
     * schema and we make no assumptions about their shape.
     *
     * @param list<OperationArgument> $operationArguments
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws Exception
     */
    public static function extend(Schema $schema, array $operationArguments) : Schema
    {
        /**
         * @var array<string, list<string>> $locationsByDirective
         */
        $locationsByDirective = [];

        foreach ($operationArguments as $operationArgument) {
            if ($operationArgument->directive === null) {
                continue;
            }

            // Leave directives that the user's schema already defines untouched.
            if ($schema->getDirective($operationArgument->directive) !== null) {
                continue;
            }

            // An empty operations list means the argument may target any operation type.
            $operations = $operationArgument->operations === []
                ? ['query', 'mutation']
                : $operationArgument->operations;

            foreach ($operations as $operation) {
                $location = match ($operation) {
                    'query' => 'QUERY',
                    'mutation' => 'MUTATION',
                };

                if (in_array($location, $locationsByDirective[$operationArgument->directive] ?? [], true)) {
                    continue;
                }

                $locationsByDirective[$operationArgument->directive][] = $location;
            }
        }

        foreach ($locationsByDirective as $name => $locations) {
            sort($locations);

            $schema = SchemaExtender::extend(
                $schema,
                Parser::parse(sprintf('directive @%s on %s', $name, implode(' | ', $locations))),
            );
        }

        return $schema;
    }
}
