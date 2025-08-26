<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferReversalDetails\Resolution;
use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferReversalDetails\Total;

// This file was automatically generated and should not be edited.

final class TransferReversalDetails
{
    public string $createdAt {
        get => $this->createdAt ??= $this->data['createdAt'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @var null|list<Resolution>
     */
    public ?array $resolutions {
        get => $this->resolutions ??= $this->data['resolutions'] !== null ? array_map(fn($item) => new Resolution($item), $this->data['resolutions']) : null;
    }

    public ?string $returnMethod {
        get => $this->returnMethod ??= $this->data['returnMethod'] !== null ? $this->data['returnMethod'] : null;
    }

    public ?string $returnedAt {
        get => $this->returnedAt ??= $this->data['returnedAt'] !== null ? $this->data['returnedAt'] : null;
    }

    public string $state {
        get => $this->state ??= $this->data['state'];
    }

    public Total $total {
        get => $this->total ??= new Total($this->data['total']);
    }

    /**
     * @param array{
     *     'createdAt': string,
     *     'id': string,
     *     'resolutions': null|list<array{
     *         'createdAt': string,
     *         'id': string,
     *         'total': array{
     *             'amount': string,
     *             'currency': string,
     *         },
     *     }>,
     *     'returnMethod': null|string,
     *     'returnedAt': null|string,
     *     'state': string,
     *     'total': array{
     *         'amount': string,
     *         'currency': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
