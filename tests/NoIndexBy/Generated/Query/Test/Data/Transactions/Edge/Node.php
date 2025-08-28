<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge;

use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node\Workflow;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class Node
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public ?Workflow $workflow {
        get => $this->workflow ??= $this->data['workflow'] !== null ? new Workflow($this->data['workflow']) : null;
    }

    /**
     * @param array{
     *     'id': string,
     *     'workflow': null|array{
     *         'request': null|array{
     *             'id': string,
     *             'items': list<array{
     *                 '__typename': string,
     *                 'id': string,
     *             }>,
     *         },
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
