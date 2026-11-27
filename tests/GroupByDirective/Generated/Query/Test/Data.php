<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\Customers;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\IssuesByProject;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\IssuesByState;

// This file was automatically generated and should not be edited.

final class Data
{
    public Customers $customers {
        get => $this->customers ??= new Customers($this->data['customers']);
    }

    /**
     * @var array<string, list<IssuesByProject>>
     */
    public array $issuesByProject {
        get => $this->issuesByProject ??= (function() {
            $result = [];
            foreach ($this->data['issuesByProject'] as $item) {
                $result[$item['project']['id']][] = new IssuesByProject($item);
            }

            return $result;
        })();
    }

    /**
     * @var array<string, list<IssuesByState>>
     */
    public array $issuesByState {
        get => $this->issuesByState ??= (function() {
            $result = [];
            foreach ($this->data['issuesByState'] as $item) {
                $result[$item['state']][] = new IssuesByState($item);
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
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     'issuesByProject': list<array{
     *         'id': string,
     *         'project': array{
     *             'id': string,
     *             'name': string,
     *             ...,
     *         },
     *         'title': string,
     *         ...,
     *     }>,
     *     'issuesByState': list<array{
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
