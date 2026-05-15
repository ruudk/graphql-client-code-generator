<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data;

use Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data\Nested\Inner;

// This file was automatically generated and should not be edited.

final class Nested
{
    public Inner $inner {
        get => $this->inner ??= new Inner($this->data['inner']);
    }

    /**
     * @param array{
     *     'inner': array{
     *         '__typename': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
