<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Viewer\Manager;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public ?NodeId $bestFriendId {
        get => $this->bestFriendId ??= $this->data['bestFriendId'] !== null ? $this->decoders['ID']->decode($this->data['bestFriendId']) : null;
    }

    /**
     * @var list<NodeId>
     */
    public array $friendIds {
        get => $this->friendIds ??= array_map(fn($item) => $this->decoders['ID']->decode($item), $this->data['friendIds']);
    }

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
     *     'bestFriendId': null|string,
     *     'friendIds': list<string>,
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
