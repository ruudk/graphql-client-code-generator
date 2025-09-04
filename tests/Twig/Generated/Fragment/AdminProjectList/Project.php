<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectList;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectRow;

// This file was automatically generated and should not be edited.

#[Generated(source: 'tests/Twig/templates/list.html.twig')]
final class Project
{
    public AdminProjectRow $adminProjectRow {
        get => $this->adminProjectRow ??= new AdminProjectRow($this->data);
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'id': string,
     *     'name': string,
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
