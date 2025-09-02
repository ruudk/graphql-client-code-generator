<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\ViewerProjects1d8480;

use Exception;

// This file was automatically generated and should not be edited.

final class ViewerProjectsQueryFailedException extends Exception
{
    public function __construct(
        public readonly Data $data,
    ) {
        parent::__construct(sprintf(
            'ViewerProjectsQueryFailedException failed%s',
            $data->errors !== [] ? sprintf(': %s', $data->errors[0]->message) : '',
        ));
    }
}
