<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\ProjectId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class Narrowed
{
    public ProjectId $id {
        get => $this->id ??= $this->decoders['ID']->decode($this->data['id'], ProjectId::class);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @var array{
     *     '__typename': string,
     *     'id': string,
     *     'name': string,
     *     ...,
     * }
     */
    private readonly array $data;

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
     * @throws NodeNotFoundException
     */
    public function __construct(
        array $data,
        private readonly array $decoders,
    ) {
        if ($data['__typename'] !== 'Project' || ! array_key_exists('name', $data)) {
            throw NodeNotFoundException::unexpectedType('Query', 'narrowed', 'Project', $data['__typename']);
        }

        $this->data = $data;
    }
}
