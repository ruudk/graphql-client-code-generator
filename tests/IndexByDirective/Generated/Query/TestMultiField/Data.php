<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField\Data\CustomerConnection;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField\Data\Issu;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField\Data\Project;

// This file was automatically generated and should not be edited.

final class Data
{
    public CustomerConnection $customers {
        get => $this->customers ??= new CustomerConnection($this->data['customers']);
    }

    /**
     * @var array<int,Issu>
     */
    public array $issues {
        get => $this->issues ??= array_combine(
            array_column($this->data['issues'], 'id'),
            array_map(fn($item) => new Issu($item), $this->data['issues']),
        );
    }

    /**
     * @var array<string,array<string,Project>>
     */
    public array $projects {
        get => $this->projects ??= (function() {
            $result = [];
            foreach ($this->data['projects'] as $item) {
                $result[$item['id']][$item['name']] = new Project($item);
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
     *                 'id': int,
     *                 'name': string,
     *             },
     *         }>,
     *     },
     *     'issues': list<array{
     *         'id': int,
     *         'name': string,
     *     }>,
     *     'projects': list<array{
     *         'id': string,
     *         'name': string,
     *     }>,
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
