<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer;

use Exception;

// This file was automatically generated and should not be edited.

final class ViewerQueryFailedException extends Exception
{
    public function __construct(
        public readonly Data $data,
    ) {
        parent::__construct(sprintf(
            'ViewerQueryFailedException failed%s',
            $data->errors !== [] ? sprintf(': %s', $data->errors[0]->message) : '',
        ));
    }
}
