<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data\Workflow;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?Workflow $workflow {
        get => $this->workflow ??= $this->data['workflow'] !== null ? new Workflow($this->data['workflow']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'workflow': null|array{
     *         'id': scalar,
     *         'transaction': null|array{
     *             'transfers': list<array{
     *                 'canBeCollected': bool,
     *                 'customer': array{
     *                     'id': scalar,
     *                 },
     *                 'id': scalar,
     *                 'transferReversals': list<array{
     *                     'id': scalar,
     *                     'transfer': array{
     *                         'metadata': scalar,
     *                     },
     *                 }>,
     *             }>,
     *         },
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
