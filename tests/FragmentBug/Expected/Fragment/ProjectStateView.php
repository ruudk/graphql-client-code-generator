<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment\ProjectStateView\Creator;

// This file was automatically generated and should not be edited.

/**
 * fragment ProjectStateView on Project {
 *   creator {
 *     id
 *   }
 * }
 */
final class ProjectStateView
{
    public Creator $creator {
        get => $this->creator ??= new Creator($this->data['creator']);
    }

    /**
     * @param array{
     *     'creator': array{
     *         'id': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
