<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search;

use Exception;

// This file was automatically generated and should not be edited.

final class SearchQueryFailedException extends Exception
{
    public function __construct(
        public readonly Data $data,
    ) {
        parent::__construct(sprintf(
            'SearchQueryFailedException failed%s',
            $data->errors !== [] ? sprintf(': %s', $data->errors[0]->message) : '',
        ));
    }
}
