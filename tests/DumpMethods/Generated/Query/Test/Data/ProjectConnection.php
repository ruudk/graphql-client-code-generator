<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\Test\Data\ProjectConnection\PageInfo;
use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\Test\Data\ProjectConnection\ProjectEdge;
use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\Test\Data\ProjectConnection\ProjectEdge\Project;

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
     * @var list<Project>
     */
    public array $nodes {
        get => array_map(fn($edge) => $edge->node, $this->edges);
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

    /**
     * @return list<ProjectEdge>
     */
    public function getEdges() : array
    {
        return $this->edges;
    }

    public function getPageInfo() : PageInfo
    {
        return $this->pageInfo;
    }

    /**
     * @return list<Project>
     */
    public function getNodes() : array
    {
        return $this->nodes;
    }
}
