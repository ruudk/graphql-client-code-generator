<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field;

// This file was automatically generated and should not be edited.

final class Single
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
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
