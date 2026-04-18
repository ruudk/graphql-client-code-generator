<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Query\Test\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Query\Test\Data\Viewer\Project\Creator;
use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\User;

// This file was automatically generated and should not be edited.

final class Project
{
    public Creator $creator {
        get => $this->creator ??= new Creator($this->data['creator']);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ?User $user {
        get => $this->user ??= $this->hooks['findUserById']->__invoke($this->data['creator']['id']);
    }

    /**
     * @param array{
     *     'creator': array{
     *         'id': string,
     *     },
     *     'name': string,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
