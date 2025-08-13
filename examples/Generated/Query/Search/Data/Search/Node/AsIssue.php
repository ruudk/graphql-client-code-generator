<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\Search\Node;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class AsIssue
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Issue'];

    public int $number {
        get => $this->data['number'];
    }

    public string $title {
        get => $this->data['title'];
    }

    /**
     * @param array{
     *     'number': int,
     *     'title': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
