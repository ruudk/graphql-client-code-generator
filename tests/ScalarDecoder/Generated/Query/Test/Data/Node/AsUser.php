<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Node;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;

// This file was automatically generated and should not be edited.

final class AsUser
{
    public UserId $id {
        get => $this->id ??= $this->decoders['ID']->decode($this->data['id'], UserId::class);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     '__typename': 'User',
     *     'id': string,
     *     'name': string,
     *     ...,
     * } $data
     * @param array{
     *     'ID': NodeIdDecoder,
     *     ...,
     * } $decoders
     */
    public function __construct(
        private readonly array $data,
        private readonly array $decoders,
    ) {}
}
