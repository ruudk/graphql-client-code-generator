<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

/**
 * ... on User {
 *   login
 * }
 */
final class AsUser
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsViewer $asViewer {
        get => $this->asViewer ??= in_array($this->data['__typename'], AsViewer::POSSIBLE_TYPES, true) ? new AsViewer($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asViewer
     */
    public bool $isViewer {
        get => $this->isViewer ??= in_array($this->data['__typename'], AsViewer::POSSIBLE_TYPES, true);
    }

    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'login': string,
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
