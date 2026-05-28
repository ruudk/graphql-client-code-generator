<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data;

// This file was automatically generated and should not be edited.

final class FireAndForget
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
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
