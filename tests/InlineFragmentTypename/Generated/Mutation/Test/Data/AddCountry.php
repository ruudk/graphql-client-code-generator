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
        get {
            if (isset($this->asCountry)) {
                return $this->asCountry;
            }

            if ($this->data['__typename'] !== 'Country') {
                return $this->asCountry = null;
            }

            if (! array_key_exists('id', $this->data)) {
                return $this->asCountry = null;
            }

            if (! array_key_exists('name', $this->data)) {
                return $this->asCountry = null;
            }

            return $this->asCountry = new AsCountry($this->data);
        }
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
        get {
            if (isset($this->asUnsupportedCountryError)) {
                return $this->asUnsupportedCountryError;
            }

            if ($this->data['__typename'] !== 'UnsupportedCountryError') {
                return $this->asUnsupportedCountryError = null;
            }

            if (! array_key_exists('code', $this->data)) {
                return $this->asUnsupportedCountryError = null;
            }

            return $this->asUnsupportedCountryError = new AsUnsupportedCountryError($this->data);
        }
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
     *     '__typename': string,
     *     'code'?: string,
     *     'id'?: string,
     *     'name'?: string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
