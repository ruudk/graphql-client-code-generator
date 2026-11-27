<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\Customers;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\IssuesByAssigneeAndState;

// This file was automatically generated and should not be edited.

final class Data
{
    public Customers $customers {
        get => $this->customers ??= new Customers($this->data['customers']);
    }

    /**
     * @var array<int, array<string, list<IssuesByAssigneeAndState>>>
     */
    public array $issuesByAssigneeAndState {
        get => $this->issuesByAssigneeAndState ??= (function() {
            $result = [];
            foreach ($this->data['issuesByAssigneeAndState'] as $item) {
                $result[$item['assignee']['id']][$item['state']][] = new IssuesByAssigneeAndState($item);
            }

            return $result;
        })();
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'customers': array{
     *         'edges': list<array{
     *             'node': array{
     *                 'country': string,
     *                 'id': int,
     *                 'name': string,
     *                 'tier': string,
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     'issuesByAssigneeAndState': list<array{
     *         'assignee': array{
     *             'id': int,
     *             ...,
     *         },
     *         'id': string,
     *         'state': string,
     *         'title': string,
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
