<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection\Node;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Fragment\PullRequestInfo;

// This file was automatically generated and should not be edited.

/**
 * ... on Issue {
 *   number
 *   title
 * }
 */
final class AsIssue
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Issue'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public int $number {
        get => $this->number ??= $this->data['number'];
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

    public string $title {
        get => $this->title ??= $this->data['title'];
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
