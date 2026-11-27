<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

/**
 * A service that turns the JSON value of a custom scalar into its PHP value, for what the server
 * answers. The generated code receives it in the `$decoders` map under the name of the scalar and calls
 * `$decoders[scalar]->method($value)`, or `->method($value, Narrower::class)` for a field with a narrower
 * type (see `SchemaConfig::withScalarFieldType()`).
 */
final readonly class ScalarDecoder
{
    /**
     * @param class-string $class
     * @param null|string $service The id of the service in the Symfony container, when it is not the class.
     */
    public function __construct(
        public string $scalar,
        public string $class,
        public string $method,
        public ?string $service = null,
    ) {}
}
