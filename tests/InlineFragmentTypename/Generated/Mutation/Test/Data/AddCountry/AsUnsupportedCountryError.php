<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry;

// This file was automatically generated and should not be edited.

final class AsUnsupportedCountryError
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $code {
        get => $this->code ??= $this->data['code'];
    }

    /**
     * @param array{
     *     '__typename': 'UnsupportedCountryError',
     *     'code': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
