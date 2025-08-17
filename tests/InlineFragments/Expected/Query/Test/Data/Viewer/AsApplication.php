<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

/**
 * ... on Application {
 *   url
 * }
 */
final class AsApplication
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Application'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public AsUser $asUser {
        get => $this->asUser ??= new AsUser($this->data);
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

    public string $url {
        get => $this->url ??= $this->data['url'];
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
