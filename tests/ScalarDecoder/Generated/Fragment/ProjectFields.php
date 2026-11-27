<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\ProjectId;

// This file was automatically generated and should not be edited.

final class ProjectFields
{
    public ProjectId $id {
        get => $this->id ??= $this->decoders['ID']->decode($this->data['id'], ProjectId::class);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
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
