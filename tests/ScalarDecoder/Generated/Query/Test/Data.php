<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Narrowed;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Node;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Project;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?Narrowed $narrowed {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->narrowed ??= $this->data['narrowed'] !== null ? new Narrowed($this->data['narrowed'], $this->decoders) : null;
    }

    public ?Node $node {
        get => $this->node ??= $this->data['node'] !== null ? new Node($this->data['node'], $this->decoders) : null;
    }

    /**
     * @var array<string, Project>
     */
    public array $projects {
        get => $this->projects ??= array_combine(
            array_column($this->data['projects'], 'id'),
            array_map(fn($item) => new Project($item, $this->hooks, $this->decoders), $this->data['projects']),
        );
    }

    public Viewer $viewer {
        get => $this->viewer ??= new Viewer($this->data['viewer'], $this->decoders);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'narrowed': null|array{
     *         '__typename': string,
     *         'id': string,
     *         'name'?: string,
     *         ...,
     *     },
     *     'node': null|array{
     *         '__typename': string,
     *         'id': string,
     *         'name'?: string,
     *         ...,
     *     },
     *     'projects': list<array{
     *         'id': string,
     *         'name': string,
     *         'ownerId': string,
     *         ...,
     *     }>,
     *     'viewer': array{
     *         'bestFriendId': null|string,
     *         'friendIds': list<string>,
     *         'id': string,
     *         'manager': null|array{
     *             'id': string,
     *             ...,
     *         },
     *         'name': string,
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
     * @param array{
     *     'ID': NodeIdDecoder,
     *     ...,
     * } $decoders
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
        private readonly array $decoders,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
