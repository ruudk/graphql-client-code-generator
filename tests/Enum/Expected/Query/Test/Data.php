<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Enum\Expected\Query\Test;

use Ruudk\GraphQLCodeGenerator\Enum\Expected\Enum\Role;
use Ruudk\GraphQLCodeGenerator\Enum\Expected\Enum\State;

// This file was automatically generated and should not be edited.

/**
 * query Test {
 *   accountStatus
 *   role
 * }
 */
final class Data
{
    public ?State $accountStatus {
        get => $this->accountStatus ??= $this->data['accountStatus'] !== null ? State::from($this->data['accountStatus']) : null;
    }

    public ?Role $role {
        get => $this->role ??= $this->data['role'] !== null ? Role::from($this->data['role']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'accountStatus': null|string,
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
