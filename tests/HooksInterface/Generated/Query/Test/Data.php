<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksInterface\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Query\Test\Data\Node;

// This file was automatically generated and should not be edited.

final class Data
{
    public Node $node {
        get => $this->node ??= new Node($this->data['node'], $this->hooks);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'node': array{
     *         '__typename': string,
     *         'ownerId'?: string,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     * @param array{
     *     'findOwner': FindOwnerHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
