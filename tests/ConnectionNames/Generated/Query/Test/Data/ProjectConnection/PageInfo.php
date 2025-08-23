<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test\Data\ProjectConnection;

// This file was automatically generated and should not be edited.

final class PageInfo
{
    public ?string $endCursor {
        get => $this->endCursor ??= $this->data['endCursor'] !== null ? $this->data['endCursor'] : null;
    }

    public bool $hasNextPage {
        get => $this->hasNextPage ??= $this->data['hasNextPage'];
    }

    public bool $hasPreviousPage {
        get => $this->hasPreviousPage ??= $this->data['hasPreviousPage'];
    }

    public ?string $startCursor {
        get => $this->startCursor ??= $this->data['startCursor'] !== null ? $this->data['startCursor'] : null;
    }

    /**
     * @param array{
     *     'endCursor': null|string,
     *     'hasNextPage': bool,
     *     'hasPreviousPage': bool,
     *     'startCursor': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
