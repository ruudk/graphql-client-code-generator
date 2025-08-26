<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Enum\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\Enum\CustomPriority;
use Ruudk\GraphQLCodeGenerator\Enum\Generated\Enum\Role;
use Ruudk\GraphQLCodeGenerator\Enum\Generated\Enum\State;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?State $accountStatus {
        get => $this->accountStatus ??= $this->data['accountStatus'] !== null ? State::tryFrom($this->data['accountStatus']) ?? State::Unknown__ : null;
    }

    public ?Role $otherRole {
        get => $this->otherRole ??= $this->data['otherRole'] !== null ? Role::tryFrom($this->data['otherRole']) ?? Role::Unknown__ : null;
    }

    public ?CustomPriority $priority {
        get => $this->priority ??= $this->data['priority'] !== null ? CustomPriority::from($this->data['priority']) : null;
    }

    public ?Role $role {
        get => $this->role ??= $this->data['role'] !== null ? Role::tryFrom($this->data['role']) ?? Role::Unknown__ : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'accountStatus': null|string,
     *     'otherRole': null|string,
     *     'priority': null|string,
     *     'role': null|string,
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
