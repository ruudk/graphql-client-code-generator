<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Fragment;

// This file was automatically generated and should not be edited.

/**
 * fragment PullRequestInfo on PullRequest {
 *   number
 *   title
 *   merged
 * }
 */
final class PullRequestInfo
{
    public bool $merged {
        get => $this->merged ??= $this->data['merged'];
    }

    public int $number {
        get => $this->number ??= $this->data['number'];
    }

    public string $title {
        get => $this->title ??= $this->data['title'];
    }

    /**
     * @param array{
     *     'merged': bool,
     *     'number': int,
     *     'title': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
