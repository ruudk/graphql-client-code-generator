<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\WithFragment;

use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Fragment\UserName;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?string $organizationName {
        get => $this->organizationName ??= $this->data['organization'] !== null ? $this->data['organization']['organizationName'] : null;
    }

    public UserName $userName {
        get => $this->userName ??= new UserName($this->data['viewer']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'organization': null|array{
     *         'organizationName': string,
     *         ...,
     *     },
     *     'viewer': array{
     *         'firstName': string,
     *         'lastName': string,
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
