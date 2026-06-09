<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Hook;

use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Hook\ProjectCreatorId\Creator;

// This file was automatically generated and should not be edited.

final class ProjectCreatorId
{
    public Creator $creator {
        get => $this->creator ??= new Creator($this->data['creator']);
    }

    /**
     * @param array{
     *     'creator': array{
     *         'id': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
