<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test;

use JsonSerializable;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Event;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Search;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data implements JsonSerializable
{
    /**
     * @var array<string, Event>
     */
    public array $events {
        get => $this->events ??= array_combine(
            array_column($this->data['events'], 'id'),
            array_map(fn($item) => new Event($item), $this->data['events']),
        );
    }

    /**
     * @var list<Search>
     */
    public array $search {
        get => $this->search ??= array_map(fn($item) => new Search($item), $this->data['search']);
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
     *     'events': list<array{
     *         '__typename': string,
     *         'description': null|string,
     *         'id': string,
     *         'location': null|array{
     *             'city': array{
     *                 'cityName': string,
     *                 ...,
     *             },
     *             'locationName': string,
     *             ...,
     *         },
     *         'name': string,
     *         'slug': string,
     *         'tags': list<string>,
     *         ...,
     *     }>,
     *     'search': list<array{
     *         '__typename': string,
     *         'id'?: string,
     *         'name'?: string,
     *         'title'?: string,
     *         ...,
     *     }>,
     *     'viewer': array{
     *         '__typename': string,
     *         'login'?: string,
     *         'name': string,
     *         'role'?: string,
     *         'url'?: string,
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
     *     'events': array<string, Event>,
     *     'search': list<Search>,
     *     'viewer': Viewer,
     * }
     */
    public function jsonSerialize() : array
    {
        return [
            'events' => $this->events,
            'search' => $this->search,
            'viewer' => $this->viewer,
        ];
    }
}
