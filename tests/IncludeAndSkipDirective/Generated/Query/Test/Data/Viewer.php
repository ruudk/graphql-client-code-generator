<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data;

// This file was automatically generated and should not be edited.

final class Viewer
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User'];

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getName() : string
    {
        return $this->name;
    }
}
