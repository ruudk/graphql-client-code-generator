<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Enum\IssueState;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\IssuesByAssigneeAndState\Assignee;

// This file was automatically generated and should not be edited.

final class IssuesByAssigneeAndState
{
    public Assignee $assignee {
        get => $this->assignee ??= new Assignee($this->data['assignee']);
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public IssueState $state {
        get => $this->state ??= IssueState::from($this->data['state']);
    }

    public string $title {
        get => $this->title ??= $this->data['title'];
    }

    /**
     * @param array{
     *     'assignee': array{
     *         'id': int,
     *         ...,
     *     },
     *     'id': string,
     *     'state': string,
     *     'title': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
