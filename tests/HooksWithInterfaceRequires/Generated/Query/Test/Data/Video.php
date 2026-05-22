<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Hook\NodeId;
use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Owner;

// This file was automatically generated and should not be edited.

final class Video
{
    public int $duration {
        get => $this->duration ??= $this->data['duration'];
    }

    public ?Owner $owner {
        get => $this->owner ??= $this->hooks['findOwner']->__invoke($this->buildNodeId());
    }

    /**
     * @param array{
     *     'duration': int,
     *     'id': string,
     *     ...,
     * } $data
     * @param array{
     *     'findOwner': FindOwnerHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}

    /**
     * @internal
     */
    public function buildNodeId() : NodeId
    {
        return new NodeId($this->data);
    }
}
