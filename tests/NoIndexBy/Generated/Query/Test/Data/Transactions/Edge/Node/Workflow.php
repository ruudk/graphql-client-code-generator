<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node;

use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node\Workflow\Request;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class Workflow
{
    public ?Request $request {
        get => $this->request ??= $this->data['request'] !== null ? new Request($this->data['request']) : null;
    }

    /**
     * @param array{
     *     'request': null|array{
     *         'id': string,
     *         'items': list<array{
     *             '__typename': string,
     *             'id': string,
     *         }>,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getRequest() : ?Request
    {
        return $this->request;
    }
}
