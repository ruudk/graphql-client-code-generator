<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Fragment\ProjectStateView;

// This file was automatically generated and should not be edited.

final class Creator
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User', 'Admin'];

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'id': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
