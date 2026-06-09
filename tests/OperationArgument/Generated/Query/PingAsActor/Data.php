<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Query\PingAsActor;

// This file was automatically generated and should not be edited.

final class Data
{
    public string $ping {
        get => $this->ping ??= $this->data['ping'];
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'ping': string,
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
