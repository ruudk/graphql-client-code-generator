<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\Test\Data\ProjectConnection\ProjectEdge;

// This file was automatically generated and should not be edited.

final class ProjectConnection
{
    /**
     * @var null|list<null|ProjectEdge>
     */
    public ?array $edges {
        get => $this->edges ??= $this->data['edges'] !== null ? array_map(fn($item) => $item !== null ? new ProjectEdge($item) : null, $this->data['edges']) : null;
    }

    /**
     * @param array{
     *     'edges': null|list<null|array{
     *         'cursor': string,
     *         'node': array{
     *             'id': string,
     *             'name': string,
     *         },
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
