<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Fragment\ProjectSummary\Creator;
use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\User;

// This file was automatically generated and should not be edited.

final class ProjectSummary
{
    public Creator $creator {
        get => $this->creator ??= new Creator($this->data['creator']);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ?User $user {
        get => $this->user ??= $this->hooks['findUserById']->__invoke($this->creator->id);
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
