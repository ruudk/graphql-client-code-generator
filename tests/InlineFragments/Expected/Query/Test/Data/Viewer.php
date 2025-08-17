<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query\Test\Data\Viewer\AsApplication;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query\Test\Data\Viewer\AsUser;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query\Test\Data\Viewer\AsViewer;

// This file was automatically generated and should not be edited.

/**
 * ... on Viewer {
 *   __typename
 *   ... on Viewer {
 *     name
 *   }
 *   ... on User {
 *     login
 *   }
 *   ... on Application {
 *     url
 *   }
 * }
 */
final class Viewer
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User', 'Application'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsApplication $asApplication {
        get => $this->asApplication ??= $this->data['__typename'] === 'Application' ? new AsApplication($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asApplication
     */
    public bool $isApplication {
        get => $this->isApplication ??= $this->data['__typename'] === 'Application';
    }

    public ?AsUser $asUser {
        get => $this->asUser ??= $this->data['__typename'] === 'User' ? new AsUser($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asUser
     */
    public bool $isUser {
        get => $this->isUser ??= $this->data['__typename'] === 'User';
    }

    public AsViewer $asViewer {
        get => $this->asViewer ??= new AsViewer($this->data);
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'login': string,
     *     'name': string,
     *     'url': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
