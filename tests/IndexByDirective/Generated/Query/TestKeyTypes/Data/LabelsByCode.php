<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestKeyTypes\Data;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\ValueObjects\LabelCode;

// This file was automatically generated and should not be edited.

final class LabelsByCode
{
    public LabelCode $code {
        get => $this->code ??= new LabelCode($this->data['code']);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'code': string,
     *     'name': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
