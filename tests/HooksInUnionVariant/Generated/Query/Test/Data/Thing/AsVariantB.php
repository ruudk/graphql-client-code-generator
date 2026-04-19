<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\Data\Thing;

// This file was automatically generated and should not be edited.

final class AsVariantB
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['VariantB'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $realFieldB {
        get => $this->realFieldB ??= $this->data['realFieldB'];
    }

    /**
     * @param array{
     *     '__typename': 'VariantB',
     *     'id': string,
     *     'realFieldB': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
