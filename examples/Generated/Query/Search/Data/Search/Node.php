<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\Search;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Fragment\PullRequestInfo;
use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\Search\Node\AsIssue;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

/**
 * {
 *   __typename
 *   ... on Issue {
 *     number
 *     title
 *   }
 *   ...PullRequestInfo
 * }
 */
#[Exclude]
final class Node
{
    public string $__typename {
        get => $this->data['__typename'];
    }

    public ?AsIssue $asIssue {
        get => in_array($this->__typename, AsIssue::POSSIBLE_TYPES, true) ? new AsIssue($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asIssue
     */
    public bool $isIssue {
        get => in_array($this->__typename, AsIssue::POSSIBLE_TYPES, true);
    }

    public ?PullRequestInfo $pullRequestInfo {
        get => in_array($this->__typename, PullRequestInfo::POSSIBLE_TYPES, true) ? new PullRequestInfo($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->pullRequestInfo
     */
    public bool $isPullRequestInfo {
        get => in_array($this->__typename, PullRequestInfo::POSSIBLE_TYPES, true);
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
