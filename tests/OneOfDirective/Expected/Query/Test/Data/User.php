<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OneOfDirective\Expected\Query\Test\Data;

// This file was automatically generated and should not be edited.

/**
 * ... on User {
 *   id
 *   email
 * }
 */
final class User
{
    public string $email {
        get => $this->email ??= $this->data['email'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'email': string,
     *     'id': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
