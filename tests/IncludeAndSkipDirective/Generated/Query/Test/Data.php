<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\Admin;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\Admin2;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\User2;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?Admin $admin {
        get {
            if (isset($this->admin)) {
                return $this->admin;
            }

            if (! array_key_exists('admin', $this->data)) {
                return $this->admin = null;
            }

            return $this->admin = new Admin($this->data['admin']);
        }
    }

    /**
     * @throws NodeNotFoundException
     */
    public Admin $adminOrThrow {
        get => $this->admin ?? throw NodeNotFoundException::create('Query', 'admin');
    }

    public ?Admin2 $admin2 {
        get {
            if (isset($this->admin2)) {
                return $this->admin2;
            }

            if (! array_key_exists('admin2', $this->data)) {
                return $this->admin2 = null;
            }

            return $this->admin2 = new Admin2($this->data['admin2']);
        }
    }

    /**
     * @throws NodeNotFoundException
     */
    public Admin2 $admin2OrThrow {
        get => $this->admin2 ?? throw NodeNotFoundException::create('Query', 'admin2');
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
     *     'admin'?: array{
     *         'name': string,
     *     },
     *     'admin2'?: array{
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

    public function getAdmin() : ?Admin
    {
        return $this->admin;
    }

    /**
     * @throws NodeNotFoundException
     */
    public function getAdminOrThrow() : Admin
    {
        return $this->adminOrThrow;
    }

    public function getAdmin2() : ?Admin2
    {
        return $this->admin2;
    }

    /**
     * @throws NodeNotFoundException
     */
    public function getAdmin2OrThrow() : Admin2
    {
        return $this->admin2OrThrow;
    }

    public function getUser2() : User2
    {
        return $this->user2;
    }

    public function getViewer() : Viewer
    {
        return $this->viewer;
    }

    /**
     * @return list<Error>
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
}
