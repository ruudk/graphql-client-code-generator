<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoderWithoutHooks\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoderWithoutHooks\Generated\Query\Test\Data\Viewer\Manager;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public UserId $id {
        get => $this->id ??= $this->decoders['ID']->decode($this->data['id'], UserId::class);
    }

    public ?Manager $manager {
        get => $this->manager ??= $this->data['manager'] !== null ? new Manager($this->data['manager'], $this->decoders) : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'id': string,
     *     'manager': null|array{
     *         'id': string,
     *         ...,
     *     },
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
