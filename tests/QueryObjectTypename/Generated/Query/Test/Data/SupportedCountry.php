<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\SupportedCountry\Error;
use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\SupportedCountry\Info;

// This file was automatically generated and should not be edited.

final class SupportedCountry
{
    public ?Error $error {
        get => $this->error ??= $this->data['error'] !== null ? new Error($this->data['error']) : null;
    }

    public ?Info $info {
        get => $this->info ??= $this->data['info'] !== null ? new Info($this->data['info']) : null;
    }

    /**
     * @param array{
     *     'error': null|array{
     *         '__typename': string,
     *         ...<int|string, mixed>,
     *     },
     *     'info': null|array{
     *         'name': string,
     *         ...<int|string, mixed>,
     *     },
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
