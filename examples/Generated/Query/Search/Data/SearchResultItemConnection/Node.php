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
        get => $this->asIssue ??= $this->data['__typename'] === 'Issue' ? new AsIssue($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asIssue
     */
    public bool $isIssue {
        get => $this->isIssue ??= $this->data['__typename'] === 'Issue';
    }

    public ?PullRequestInfo $pullRequestInfo {
        get => $this->pullRequestInfo ??= $this->data['__typename'] === 'PullRequest' ? new PullRequestInfo($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->pullRequestInfo
     */
    public bool $isPullRequestInfo {
        get => $this->isPullRequestInfo ??= $this->data['__typename'] === 'PullRequest';
    }

    /**
     * @param array{
     *     '__typename': 'App',
     * }|array{
     *     '__typename': 'Discussion',
     * }|array{
     *     '__typename': 'Issue',
     *     'number': int,
     *     'title': string,
     * }|array{
     *     '__typename': 'MarketplaceListing',
     * }|array{
     *     '__typename': 'Organization',
     * }|array{
     *     '__typename': 'PullRequest',
     *     'merged': bool,
     *     'number': int,
     *     'title': string,
     * }|array{
     *     '__typename': 'Repository',
     * }|array{
     *     '__typename': 'User',
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
