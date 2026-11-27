<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Node\AsUser;

// This file was automatically generated and should not be edited.

final class Node
{
    public ?AsUser $asUser {
        get {
            if (isset($this->asUser)) {
                return $this->asUser;
            }

            if ($this->data['__typename'] !== 'User') {
                return $this->asUser = null;
            }

            if (! array_key_exists('id', $this->data)) {
                return $this->asUser = null;
            }

            if (! array_key_exists('name', $this->data)) {
                return $this->asUser = null;
            }

            return $this->asUser = new AsUser($this->data, $this->decoders);
        }
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asUser
     */
    public bool $isUser {
        get => $this->isUser ??= $this->data['__typename'] === 'User';
    }

    public NodeId $id {
        get => $this->id ??= $this->decoders['ID']->decode($this->data['id']);
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'id': string,
     *     'name'?: string,
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
