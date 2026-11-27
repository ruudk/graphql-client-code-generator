<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\IssuesByProject\Project;

// This file was automatically generated and should not be edited.

final class IssuesByProject
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public Project $project {
        get => $this->project ??= new Project($this->data['project']);
    }

    public string $title {
        get => $this->title ??= $this->data['title'];
    }

    /**
     * @param array{
     *     'id': string,
     *     'project': array{
     *         'id': string,
     *         'name': string,
     *         ...,
     *     },
     *     'title': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
