<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry;

// This file was automatically generated and should not be edited.

final class AsSupportedCountryError
{
    /**
     * @api
     */
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    /**
     * @param array{
     *     '__typename': 'SupportedCountryError',
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
