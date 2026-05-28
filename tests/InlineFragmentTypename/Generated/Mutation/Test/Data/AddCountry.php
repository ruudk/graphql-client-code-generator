<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data;

use Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry\AsCountry;
use Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry\AsSupportedCountryError;
use Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry\AsUnsupportedCountryError;

// This file was automatically generated and should not be edited.

final class AddCountry
{
    public ?AsCountry $asCountry {
        get => $this->asCountry ??= $this->data['__typename'] === 'Country' ? new AsCountry($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asCountry
     */
    public bool $isCountry {
        get => $this->isCountry ??= $this->data['__typename'] === 'Country';
    }

    public ?AsSupportedCountryError $asSupportedCountryError {
        get => $this->asSupportedCountryError ??= $this->data['__typename'] === 'SupportedCountryError' ? new AsSupportedCountryError($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asSupportedCountryError
     */
    public bool $isSupportedCountryError {
        get => $this->isSupportedCountryError ??= $this->data['__typename'] === 'SupportedCountryError';
    }

    public ?AsUnsupportedCountryError $asUnsupportedCountryError {
        get => $this->asUnsupportedCountryError ??= $this->data['__typename'] === 'UnsupportedCountryError' ? new AsUnsupportedCountryError($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asUnsupportedCountryError
     */
    public bool $isUnsupportedCountryError {
        get => $this->isUnsupportedCountryError ??= $this->data['__typename'] === 'UnsupportedCountryError';
    }

    /**
     * @param array{
     *     '__typename': 'Country',
     *     'id': string,
     *     'name': string,
     * }|array{
     *     '__typename': 'SupportedCountryError',
     * }|array{
     *     '__typename': 'UnsupportedCountryError',
     *     'code': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
