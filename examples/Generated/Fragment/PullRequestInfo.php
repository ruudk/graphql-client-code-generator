<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Fragment;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

/**
 * fragment PullRequestInfo on PullRequest {
 *   number
 *   title
 *   merged
 * }
 */
#[Exclude]
final class PullRequestInfo
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['PullRequest'];

    public bool $merged {
        get => $this->data['merged'];
    }

    public int $number {
        get => $this->data['number'];
    }

    public string $title {
        get => $this->data['title'];
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
