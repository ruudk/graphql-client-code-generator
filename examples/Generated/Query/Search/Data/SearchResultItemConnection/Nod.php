<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Fragment\PullRequestInfo;
use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection\Nod\AsIssue;

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
final class Nod
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsIssue $asIssue {
        get => $this->asIssue ??= $this->data['__typename'] === 'Issue' ? new AsIssue($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asIssue
     */
    public bool $isIssue {
        get => $this->isIssue ??= $this->data['__typename'] === 'Issue';
    }

    public ?PullRequestInfo $pullRequestInfo {
        get => $this->pullRequestInfo ??= $this->data['__typename'] === 'PullRequest' ? new PullRequestInfo($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->pullRequestInfo
     */
    public bool $isPullRequestInfo {
        get => $this->isPullRequestInfo ??= $this->data['__typename'] === 'PullRequest';
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
