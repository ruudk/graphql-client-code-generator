<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test\Data\ProjectConnection\PageInfo;
use Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test\Data\ProjectConnection\ProjectEdge;

// This file was automatically generated and should not be edited.

final class ProjectConnection
{
    /**
     * @var list<ProjectEdge>
     */
    public array $edges {
        get => $this->edges ??= array_map(fn($item) => new ProjectEdge($item), $this->data['edges']);
    }

    public PageInfo $pageInfo {
        get => $this->pageInfo ??= new PageInfo($this->data['pageInfo']);
    }

    /**
     * @param array{
     *     'edges': list<array{
     *         'cursor': string,
     *         'node': array{
     *             'id': string,
     *             'name': string,
     *             'status': string,
     *         },
     *     }>,
     *     'pageInfo': array{
     *         'endCursor': null|string,
     *         'hasNextPage': bool,
     *         'hasPreviousPage': bool,
     *         'startCursor': null|string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
