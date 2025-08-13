<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer\Data\Viewer;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

/**
 * query Viewer {
 *   viewer {
 *     __typename
 *     login
 *   }
 * }
 */
#[Exclude]
final class Data
{
    public Viewer $viewer {
        get => new Viewer($this->data['viewer']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'viewer': array{
     *         '__typename': string,
     *         'login': string,
     *     },
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
