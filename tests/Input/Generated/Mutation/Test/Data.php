<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Input\Generated\Mutation\Test;

// This file was automatically generated and should not be edited.

final class Data
{
    public bool $createUser {
        get => $this->createUser ??= $this->data['createUser'];
    }

    public string $sayHello {
        get => $this->sayHello ??= $this->data['sayHello'];
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'createUser': bool,
     *     'sayHello': string,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
