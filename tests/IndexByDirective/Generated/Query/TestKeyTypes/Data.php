<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestKeyTypes;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestKeyTypes\Data\LabelsByCode;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestKeyTypes\Data\LabelsByStatus;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var array<string, LabelsByCode>
     */
    public array $labelsByCode {
        get => $this->labelsByCode ??= array_combine(
            array_column($this->data['labelsByCode'], 'code'),
            array_map(fn($item) => new LabelsByCode($item), $this->data['labelsByCode']),
        );
    }

    /**
     * @var array<string, LabelsByStatus>
     */
    public array $labelsByStatus {
        get => $this->labelsByStatus ??= array_combine(
            array_column($this->data['labelsByStatus'], 'status'),
            array_map(fn($item) => new LabelsByStatus($item), $this->data['labelsByStatus']),
        );
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'labelsByCode': list<array{
     *         'code': string,
     *         'name': string,
     *         ...,
     *     }>,
     *     'labelsByStatus': list<array{
     *         'name': string,
     *         'status': string,
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
