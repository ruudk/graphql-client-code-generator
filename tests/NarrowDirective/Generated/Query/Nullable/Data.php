<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\Nullable;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\Nullable\Data\Node;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?Node $node {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->node ??= $this->data['node'] !== null ? new Node($this->data['node']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'node': null|array{
     *         '__typename': string,
     *         'login'?: string,
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
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
