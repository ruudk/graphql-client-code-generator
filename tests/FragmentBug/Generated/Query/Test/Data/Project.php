<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Fragment\ProjectView;

// This file was automatically generated and should not be edited.

final class Project
{
    public ProjectView $projectView {
        get => $this->projectView ??= new ProjectView($this->data);
    }

    /**
     * @param array{
     *     'creator': array{
     *         '__typename': string,
     *         'id': string,
     *         'name'?: string,
     *         'role'?: string,
     *     },
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
