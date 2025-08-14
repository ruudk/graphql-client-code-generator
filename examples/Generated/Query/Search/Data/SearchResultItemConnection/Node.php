<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Fragment\PullRequestInfo;
use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection\Node\AsIssue;

// This file was automatically generated and should not be edited.

/**
 * ... on SearchResultItem {
 *   __typename
 *   ... on Issue {
 *     number
 *     title
 *   }
 *   ...PullRequestInfo
 * }
 */
final class Node
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsIssue $asIssue {
        get => $this->asIssue ??= in_array($this->__typename, AsIssue::POSSIBLE_TYPES, true) ? new AsIssue($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asIssue
     */
    public bool $isIssue {
        get => $this->isIssue ??= in_array($this->__typename, AsIssue::POSSIBLE_TYPES, true);
    }

    public ?PullRequestInfo $pullRequestInfo {
        get => $this->pullRequestInfo ??= in_array($this->__typename, PullRequestInfo::POSSIBLE_TYPES, true) ? new PullRequestInfo($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->pullRequestInfo
     */
    public bool $isPullRequestInfo {
        get => $this->isPullRequestInfo ??= in_array($this->__typename, PullRequestInfo::POSSIBLE_TYPES, true);
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'merged': bool,
     *     'number': int,
     *     'title': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
