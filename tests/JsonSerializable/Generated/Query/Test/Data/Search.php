<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data;

use JsonSerializable;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Search\AsEvent;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Search\AsVenue;

// This file was automatically generated and should not be edited.

final class Search implements JsonSerializable
{
    public ?AsEvent $asEvent {
        get {
            if (isset($this->asEvent)) {
                return $this->asEvent;
            }

            if ($this->data['__typename'] !== 'Event') {
                return $this->asEvent = null;
            }

            if (! array_key_exists('id', $this->data)) {
                return $this->asEvent = null;
            }

            if (! array_key_exists('name', $this->data)) {
                return $this->asEvent = null;
            }

            return $this->asEvent = new AsEvent($this->data);
        }
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asEvent
     */
    public bool $isEvent {
        get => $this->isEvent ??= $this->data['__typename'] === 'Event';
    }

    public ?AsVenue $asVenue {
        get {
            if (isset($this->asVenue)) {
                return $this->asVenue;
            }

            if ($this->data['__typename'] !== 'Venue') {
                return $this->asVenue = null;
            }

            if (! array_key_exists('id', $this->data)) {
                return $this->asVenue = null;
            }

            if (! array_key_exists('title', $this->data)) {
                return $this->asVenue = null;
            }

            return $this->asVenue = new AsVenue($this->data);
        }
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asVenue
     */
    public bool $isVenue {
        get => $this->isVenue ??= $this->data['__typename'] === 'Venue';
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'id'?: string,
     *     'name'?: string,
     *     'title'?: string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * @return array{
     *     'id': string,
     *     'name': string,
     * }|array{
     *     'id': string,
     *     'title': string,
     * }|array{}
     */
    public function jsonSerialize() : array
    {
        $asEvent = $this->asEvent;
        $asVenue = $this->asVenue;

        if ($asEvent !== null) {
            return [
                ...$asEvent->jsonSerialize(),
            ];
        }

        if ($asVenue !== null) {
            return [
                ...$asVenue->jsonSerialize(),
            ];
        }

        return [];
    }
}
