<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\Deprecation\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Query\Test\Data\LegacyViewer;
use Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @deprecated Use `viewer` instead.
     */
    public ?LegacyViewer $legacyViewer {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->legacyViewer ??= $this->data['legacyViewer'] !== null ? new LegacyViewer($this->data['legacyViewer']) : null;
    }

    /**
     * @throws NodeNotFoundException
     * @deprecated Use `viewer` instead.
     */
    public LegacyViewer $legacyViewerOrThrow {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->legacyViewer ?? throw NodeNotFoundException::create('Query', 'legacyViewer');
    }

    public Viewer $viewer {
        get => $this->viewer ??= new Viewer($this->data['viewer']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'legacyViewer': null|array{
     *         'name': string,
     *         ...,
     *     },
     *     'viewer': array{
     *         'knownNickname': null|string,
     *         'login': string,
     *         'name': string,
     *         'nickname': null|string,
     *         'profile': array{
     *             'bio': string,
     *             ...,
     *         },
     *         'role': string,
     *         'username': string,
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
