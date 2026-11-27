<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data\Event;

// This file was automatically generated and should not be edited.

final class Data
{
    public Event $event {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->event ??= $this->data['event'] !== null ? new Event($this->data['event']) : throw NodeNotFoundException::create('Query', 'event');
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'event': null|array{
     *         '__typename': string,
     *         'id': string,
     *         'name'?: string,
     *         'types'?: array{
     *             'edges': list<array{
     *                 'cursor': string,
     *                 'node': array{
     *                     'id': string,
     *                     'title': string,
     *                     ...,
     *                 },
     *                 ...,
     *             }>,
     *             ...,
     *         },
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
