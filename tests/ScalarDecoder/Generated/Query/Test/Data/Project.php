<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Fragment\ProjectFields;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Hook\ProjectOwnerId;

// This file was automatically generated and should not be edited.

final class Project
{
    public ?string $owner {
        get => $this->owner ??= $this->hooks['findOwner']->__invoke($this->buildProjectOwnerId());
    }

    public ProjectFields $projectFields {
        get => $this->projectFields ??= new ProjectFields($this->data, $this->decoders);
    }

    /**
     * @param array{
     *     'id': string,
     *     'name': string,
     *     'ownerId': string,
     *     ...,
     * } $data
     * @param array{
     *     'findOwner': FindOwnerHook,
     *     ...,
     * } $hooks
     * @param array{
     *     'ID': NodeIdDecoder,
     *     ...,
     * } $decoders
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
        private readonly array $decoders,
    ) {}

    /**
     * @internal
     */
    public function buildProjectOwnerId() : ProjectOwnerId
    {
        return new ProjectOwnerId($this->data, $this->decoders);
    }
}
