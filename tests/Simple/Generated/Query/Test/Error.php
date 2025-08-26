<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Simple\Generated\Query\Test;

// This file was automatically generated and should not be edited.

final readonly class Error
{
    public string $message;
    public string $code;

    /**
     * @param array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * } $error
     */
    public function __construct(array $error)
    {
        $this->message = $error['debugMessage'] ?? $error['message'];
        $this->code = $error['code'];
    }
}
