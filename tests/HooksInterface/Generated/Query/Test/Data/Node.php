<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksInterface\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Query\Test\Data\Node\AsProject;

// This file was automatically generated and should not be edited.

final class Node
{
    public ?AsProject $asProject {
        get {
            if (isset($this->asProject)) {
                return $this->asProject;
            }

            if ($this->data['__typename'] !== 'Project') {
                return $this->asProject = null;
            }

            if (! array_key_exists('ownerId', $this->data)) {
                return $this->asProject = null;
            }

            return $this->asProject = new AsProject($this->data, $this->hooks);
        }
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asProject
     */
    public bool $isProject {
        get => $this->isProject ??= $this->data['__typename'] === 'Project';
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'ownerId'?: string,
     *     ...,
     * } $data
     * @param array{
     *     'findOwner': FindOwnerHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
