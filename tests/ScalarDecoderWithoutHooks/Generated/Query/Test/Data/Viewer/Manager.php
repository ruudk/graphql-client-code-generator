<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoderWithoutHooks\Generated\Query\Test\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;

// This file was automatically generated and should not be edited.

final class Manager
{
    public UserId $id {
        get => $this->id ??= $this->decoders['ID']->decode($this->data['id'], UserId::class);
    }

    /**
     * @param array{
     *     'id': string,
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
