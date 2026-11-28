<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data;

use JsonSerializable;

// This file was automatically generated and should not be edited.

final class Event implements JsonSerializable
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?string $cityName {
        get => $this->cityName ??= $this->data['location'] !== null ? $this->data['location']['city']['cityName'] : null;
    }

    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public ?string $locationName {
        get => $this->locationName ??= $this->data['location'] !== null ? $this->data['location']['locationName'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @deprecated Use id.
     */
    public string $slug {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->slug ??= $this->data['slug'];
    }

    /**
     * @var list<string>
     */
    public array $tags {
        get => $this->tags ??= $this->data['tags'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'description': null|string,
     *     'id': string,
     *     'location': null|array{
     *         'city': array{
     *             'cityName': string,
     *             ...,
     *         },
     *         'locationName': string,
     *         ...,
     *     },
     *     'name': string,
     *     'slug': string,
     *     'tags': list<string>,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * @return array{
     *     '__typename': string,
     *     'cityName': null|string,
     *     'description': null|string,
     *     'id': string,
     *     'locationName': null|string,
     *     'name': string,
     *     'slug': string,
     *     'tags': list<string>,
     * }
     * @mago-expect analysis:deprecated-property
     */
    public function jsonSerialize() : array
    {
        return [
            '__typename' => $this->__typename,
            'cityName' => $this->cityName,
            'description' => $this->description,
            'id' => $this->id,
            'locationName' => $this->locationName,
            'name' => $this->name,
            'slug' => $this->slug,
            'tags' => $this->tags,
        ];
    }
}
