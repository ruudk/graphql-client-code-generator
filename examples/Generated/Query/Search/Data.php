<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\Search;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

/**
 * query Search {
 *   search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
 *     __typename
 *     nodes {
 *       __typename
 *       ... on Issue {
 *         number
 *         title
 *       }
 *       ...PullRequestInfo
 *     }
 *   }
 * }
 */
#[Exclude]
final class Data
{
    public Search $search {
        get => new Search($this->data['search']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'search': array{
     *         '__typename': string,
     *         'nodes': list<array{
     *             '__typename': string,
     *             'merged': bool,
     *             'number': int,
     *             'title': string,
     *         }>,
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
