<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field;

// This file was automatically generated and should not be edited.

final class SoleList
{
    /**
     * @api
     */
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
