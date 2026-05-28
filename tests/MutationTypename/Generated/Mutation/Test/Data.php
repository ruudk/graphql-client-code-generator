<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test;

use Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data\FireAndForget;
use Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data\Nested;
use Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data\WithExtraFields;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @api
     */
    public FireAndForget $fireAndForget {
        get => $this->fireAndForget ??= new FireAndForget($this->data['fireAndForget']);
    }

    /**
     * @api
     */
    public Nested $nested {
        get => $this->nested ??= new Nested($this->data['nested']);
    }

    /**
     * @api
     */
    public WithExtraFields $withExtraFields {
        get => $this->withExtraFields ??= new WithExtraFields($this->data['withExtraFields']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'fireAndForget': array{
     *         '__typename': string,
     *         ...<int|string, mixed>,
     *     },
     *     'nested': array{
     *         'inner': array{
     *             '__typename': string,
     *             ...<int|string, mixed>,
     *         },
     *         ...<int|string, mixed>,
     *     },
     *     'withExtraFields': array{
     *         '__typename': string,
     *         'name': string,
     *         ...<int|string, mixed>,
     *     },
     *     ...<int|string, mixed>,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...<int|string, mixed>,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
