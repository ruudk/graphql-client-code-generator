<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\Query\Test\Data\Viewer\Project;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public string $description {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : throw NodeNotFoundException::create('Viewer', 'description');
    }

    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    public Project $project {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->project ??= $this->data['project'] !== null ? new Project($this->data['project']) : throw NodeNotFoundException::create('Viewer', 'project');
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'login': string,
     *     'project': null|array{
     *         'id': string,
     *         'name': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
