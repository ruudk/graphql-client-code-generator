<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Enum\IssueState;

// This file was automatically generated and should not be edited.

final class IssuesByState
{
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
