<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Query\Projectsd4cba6;

use Exception;

// This file was automatically generated and should not be edited.

final class ProjectsQueryFailedException extends Exception
{
    public function __construct(
        public readonly Data $data,
    ) {
        parent::__construct(sprintf(
            'ProjectsQueryFailedException failed%s',
            $data->errors !== [] ? sprintf(': %s', $data->errors[0]->message) : '',
        ));
    }
}
