<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Query\Ping;

use Exception;

// This file was automatically generated and should not be edited.

final class PingQueryFailedException extends Exception
{
    public function __construct(
        /**
         * @api
         */
        public readonly Data $data,
    ) {
        parent::__construct(sprintf(
            'PingQueryFailedException failed%s',
            $data->errors !== [] ? sprintf(': %s', $data->errors[0]->message) : '',
        ));
    }
}
