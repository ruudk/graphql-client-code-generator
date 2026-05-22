<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Hook;

// This file was automatically generated and should not be edited.

final class ProjectContributorIds
{
    /**
     * @var list<string>
     */
    public array $contributorIds {
        get => $this->contributorIds ??= $this->data['contributorIds'];
    }

    /**
     * @param array{
     *     'contributorIds': list<string>,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
