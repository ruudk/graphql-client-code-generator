<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\Test;

// This file was automatically generated and should not be edited.

final readonly class Error
{
    public string $message;

    /**
     * @param array{
     *     'debugMessage'?: string,
     *     'message': string,
     * } $error
     */
    public function __construct(array $error)
    {
        $this->message = $error['debugMessage'] ?? $error['message'];
    }
}
