<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\List;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\List\Data\Node;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\List\Data\Search;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<null|Node>
     */
    public array $nodes {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->nodes ??= array_map(fn($item) => $item !== null ? new Node($item) : null, $this->data['nodes']);
    }

    /**
     * @var list<Search>
     */
    public array $search {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->search ??= array_map(fn($item) => new Search($item), $this->data['search']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'nodes': list<null|array{
     *         '__typename': string,
     *         'description'?: null|string,
     *         'name'?: string,
     *         ...,
     *     }>,
     *     'search': list<array{
     *         '__typename': string,
     *         'login'?: string,
     *         ...,
     *     }>,
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
