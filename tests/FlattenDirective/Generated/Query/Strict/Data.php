<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Strict;

use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class Data
{
    public string $bio {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->bio ??= $this->data['maybeViewer'] !== null ? ($this->data['maybeViewer']['bio'] !== null ? $this->data['maybeViewer']['bio'] : throw NodeNotFoundException::create('User', 'bio')) : throw NodeNotFoundException::create('Query', 'maybeViewer');
    }

    public ?string $email {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->email ??= $this->data['maybeViewer'] !== null ? ($this->data['maybeViewer']['account'] !== null ? $this->data['maybeViewer']['account']['email'] : null) : throw NodeNotFoundException::create('Query', 'maybeViewer');
    }

    public string $firstName {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->firstName ??= $this->data['maybeViewer'] !== null ? $this->data['maybeViewer']['firstName'] : throw NodeNotFoundException::create('Query', 'maybeViewer');
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'maybeViewer': null|array{
     *         'account': null|array{
     *             'email': string,
     *             ...,
     *         },
     *         'bio': null|string,
     *         'firstName': string,
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
