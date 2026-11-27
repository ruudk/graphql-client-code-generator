<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\IssuesByAssigneeAndState;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\ValueObjects\AccountId;

// This file was automatically generated and should not be edited.

final class Assignee
{
    public AccountId $id {
        get => $this->id ??= new AccountId($this->data['id']);
    }

    /**
     * @param array{
     *     'id': int,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
