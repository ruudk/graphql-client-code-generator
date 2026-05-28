<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\FeaturedUsers5a9829;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineFragment\FeaturedUsersClient;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\FeaturedUsers5a9829\Data\FeaturedUser;

// This file was automatically generated and should not be edited.

#[Generated(
    source: FeaturedUsersClient::class,
    restricted: true,
    restrictInstantiation: true,
)]
final class Data
{
    /**
     * @var list<FeaturedUser>
     */
    public array $featuredUsers {
        get => $this->featuredUsers ??= array_map(fn($item) => new FeaturedUser($item), $this->data['featuredUsers']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'featuredUsers': list<array{
     *         'firstName': string,
     *         'id': string,
     *         'lastName': string,
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
