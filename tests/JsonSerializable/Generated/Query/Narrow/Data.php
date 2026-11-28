<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Narrow;

use JsonSerializable;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Narrow\Data\Event;

// This file was automatically generated and should not be edited.

final class Data implements JsonSerializable
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
     *         'id'?: string,
     *         'name'?: string,
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

    /**
     * @return array{
     *     'event': Event,
     * }
     */
    public function jsonSerialize() : array
    {
        return [
            'event' => $this->event,
        ];
    }
}
