<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\Admin;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\Admin2;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\User2;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?Admin $admin {
        get => $this->admin ??= $this->data['admin'] !== null ? new Admin($this->data['admin']) : null;
    }

    public ?Admin2 $admin2 {
        get => $this->admin2 ??= $this->data['admin2'] !== null ? new Admin2($this->data['admin2']) : null;
    }

    public User2 $user2 {
        get => $this->user2 ??= new User2($this->data['user2']);
    }

    public Viewer $viewer {
        get => $this->viewer ??= new Viewer($this->data['viewer']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'admin': null|array{
     *         'name': string,
     *     },
     *     'admin2': null|array{
     *         'name': string,
     *     },
     *     'user2': array{
     *         'name': string,
     *     },
     *     'viewer': array{
     *         'name': string,
     *     },
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
