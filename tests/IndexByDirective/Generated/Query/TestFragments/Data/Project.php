<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestFragments\Data;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Fragment\ProjectFields;

// This file was automatically generated and should not be edited.

final class Project
{
    public ProjectFields $projectFields {
        get => $this->projectFields ??= new ProjectFields($this->data);
    }

    /**
     * @param array{
     *     'id': string,
     *     'name': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
