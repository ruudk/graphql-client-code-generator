<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Basic;

// This file was automatically generated and should not be edited.

final class Data
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $firstName {
        get => $this->firstName ??= $this->data['viewer']['firstName'];
    }

    public string $lastName {
        get => $this->lastName ??= $this->data['viewer']['lastName'];
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     '__typename': string,
     *     'viewer': array{
     *         '__typename': string,
     *         'firstName': string,
     *         'lastName': string,
     *         ...,
     *     },
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
