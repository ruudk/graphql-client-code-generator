<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\Test\Data\ProjectConnection\ProjectEdge;

use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Enum\ProjectStatus;

// This file was automatically generated and should not be edited.

final class Project
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ProjectStatus $status {
        get => $this->status ??= ProjectStatus::from($this->data['status']);
    }

    /**
     * @param array{
     *     'id': string,
     *     'name': string,
     *     'status': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getId() : string
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getStatus() : ProjectStatus
    {
        return $this->status;
    }
}
