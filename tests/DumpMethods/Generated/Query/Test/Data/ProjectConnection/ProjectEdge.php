<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\Test\Data\ProjectConnection;

use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\Test\Data\ProjectConnection\ProjectEdge\Project;

// This file was automatically generated and should not be edited.

final class ProjectEdge
{
    public string $cursor {
        get => $this->cursor ??= $this->data['cursor'];
    }

    public Project $node {
        get => $this->node ??= new Project($this->data['node']);
    }

    /**
     * @param array{
     *     'cursor': string,
     *     'node': array{
     *         'id': string,
     *         'name': string,
     *         'status': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getCursor() : string
    {
        return $this->cursor;
    }

    public function getNode() : Project
    {
        return $this->node;
    }
}
