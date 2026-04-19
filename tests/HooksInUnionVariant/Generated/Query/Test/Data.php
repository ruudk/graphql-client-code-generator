<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\Data\Thing;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Thing>
     */
    public array $things {
        get => $this->things ??= array_map(fn($item) => new Thing($item, $this->hooks), $this->data['things']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'things': list<array{
     *         '__typename': string,
     *         'id': string,
     *         'realFieldA'?: string,
     *         'realFieldB'?: string,
     *     }>,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     * @param array{
     *     'findUserById': FindUserByIdHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
