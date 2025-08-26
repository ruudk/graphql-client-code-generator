<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test;

use Exception;

// This file was automatically generated and should not be edited.

final class TestQueryFailedException extends Exception
{
    public function __construct(
        public readonly Data $data,
    ) {
        parent::__construct(sprintf(
            'TestQueryFailedException failed%s',
            $data->errors !== [] ? sprintf(': %s', $data->errors[0]->message) : '',
        ));
    }
}
