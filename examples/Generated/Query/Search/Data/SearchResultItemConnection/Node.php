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
        get {
            if (isset($this->asIssue)) {
                return $this->asIssue;
            }

            if ($this->data['__typename'] !== 'Issue') {
                return $this->asIssue = null;
            }

            if (! array_key_exists('number', $this->data)) {
                return $this->asIssue = null;
            }

            if (! array_key_exists('title', $this->data)) {
                return $this->asIssue = null;
            }

            return $this->asIssue = new AsIssue($this->data);
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->asIssue
     */
    public bool $isIssue {
        get => $this->isIssue ??= $this->data['__typename'] === 'Issue';
    }

    public ?PullRequestInfo $pullRequestInfo {
        get {
            if (isset($this->pullRequestInfo)) {
                return $this->pullRequestInfo;
            }

            if ($this->data['__typename'] !== 'PullRequest') {
                return $this->pullRequestInfo = null;
            }

            if (! array_key_exists('number', $this->data)) {
                return $this->pullRequestInfo = null;
            }

            if (! array_key_exists('title', $this->data)) {
                return $this->pullRequestInfo = null;
            }

            if (! array_key_exists('merged', $this->data)) {
                return $this->pullRequestInfo = null;
            }

            return $this->pullRequestInfo = new PullRequestInfo($this->data);
        }
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
     *     'merged'?: bool,
     *     'number'?: int,
     *     'title'?: string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
