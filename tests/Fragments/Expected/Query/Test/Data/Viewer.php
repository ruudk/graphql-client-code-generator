<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Expected\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Fragment\ViewerDetails;
use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Query\Test\Data\Viewer\Project;

// This file was automatically generated and should not be edited.

/**
 * ... on Viewer {
 *   __typename
 *   ...ViewerDetails
 *   projects {
 *     __typename
 *     ...ProjectView
 *   }
 * }
 */
final class Viewer
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    /**
     * @var list<Project>
     */
    public array $projects {
        get => $this->projects ??= array_map(fn($item) => new Project($item), $this->data['projects']);
    }

    public ?ViewerDetails $viewerDetails {
        get => $this->viewerDetails ??= in_array($this->data['__typename'], ViewerDetails::POSSIBLE_TYPES, true) ? new ViewerDetails($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->viewerDetails
     */
    public bool $isViewerDetails {
        get => $this->isViewerDetails ??= in_array($this->data['__typename'], ViewerDetails::POSSIBLE_TYPES, true);
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'login': string,
     *     'projects': list<array{
     *         '__typename': string,
     *         'description': null|string,
     *         'name': string,
     *         'state': null|string,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
