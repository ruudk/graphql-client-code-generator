<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field\MultiList;
use Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field\Single;
use Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field\SoleList;

// This file was automatically generated and should not be edited.

final class Field
{
    /**
     * @var list<MultiList>
     */
    public array $multiList {
        get => $this->multiList ??= array_map(fn($item) => new MultiList($item), $this->data['multiList']);
    }

    public Single $single {
        get => $this->single ??= new Single($this->data['single']);
    }

    /**
     * @var list<SoleList>
     */
    public array $soleList {
        get => $this->soleList ??= array_map(fn($item) => new SoleList($item), $this->data['soleList']);
    }

    /**
     * @param array{
     *     'multiList': list<array{
     *         '__typename': string,
     *         'id': string,
     *     }>,
     *     'single': array{
     *         '__typename': string,
     *     },
     *     'soleList': list<array{
     *         '__typename': string,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
