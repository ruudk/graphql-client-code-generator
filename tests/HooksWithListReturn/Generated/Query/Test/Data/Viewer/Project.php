<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Query\Test\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\FindUsersByIdsHook;
use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\User;

// This file was automatically generated and should not be edited.

final class Project
{
    /**
     * @var list<string>
     */
    public array $contributorIds {
        get => $this->contributorIds ??= array_map(fn($item) => $item, $this->data['contributorIds']);
    }

    /**
     * @var list<User>
     */
    public array $contributors {
        get => $this->contributors ??= $this->hooks['findUsersByIds']->__invoke($this->contributorIds);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'contributorIds': list<string>,
     *     'name': string,
     * } $data
     * @param array{
     *     'findUsersByIds': FindUsersByIdsHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
