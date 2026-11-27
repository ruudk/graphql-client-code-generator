<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Hook;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;

// This file was automatically generated and should not be edited.

final class ProjectOwnerId
{
    public UserId $ownerId {
        get => $this->ownerId ??= $this->decoders['ID']->decode($this->data['ownerId'], UserId::class);
    }

    /**
     * @param array{
     *     'ownerId': string,
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
