<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Query\Test\Data\User;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?User $user {
        get => $this->user ??= $this->data['user'] !== null ? new User($this->data['user']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'user': null|array{
     *         'email': string,
     *         'id': string,
     *     },
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
