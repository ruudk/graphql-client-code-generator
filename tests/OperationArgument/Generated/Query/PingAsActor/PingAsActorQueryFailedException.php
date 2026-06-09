<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Query\PingAsActor;

use Exception;

// This file was automatically generated and should not be edited.

final class PingAsActorQueryFailedException extends Exception
{
    public function __construct(
        /**
         * @api
         */
        public readonly Data $data,
    ) {
        parent::__construct(sprintf(
            'PingAsActorQueryFailedException failed%s',
            $data->errors !== [] ? sprintf(': %s', $data->errors[0]->message) : '',
        ));
    }
}
