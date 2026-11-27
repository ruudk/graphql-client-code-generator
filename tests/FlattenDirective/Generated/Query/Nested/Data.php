<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Nested;

use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Nested\Data\Project;
use Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Nested\Data\Repository;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?string $bio {
        get => $this->bio ??= $this->data['maybeViewer'] !== null ? ($this->data['maybeViewer']['bio'] !== null ? $this->data['maybeViewer']['bio'] : null) : null;
    }

    public ?string $email {
        get => $this->email ??= $this->data['maybeViewer'] !== null && $this->data['maybeViewer']['account'] !== null ? $this->data['maybeViewer']['account']['email'] : null;
    }

    public ?string $locale {
        get => $this->locale ??= $this->data['maybeViewer'] !== null && $this->data['maybeViewer']['account'] !== null && $this->data['maybeViewer']['account']['settings'] !== null ? $this->data['maybeViewer']['account']['settings']['locale'] : null;
    }

    public ?Project $project {
        get => $this->project ??= $this->data['maybeViewer'] !== null ? ($this->data['maybeViewer']['project'] !== null ? new Project($this->data['maybeViewer']['project']) : null) : null;
    }

    /**
     * @var null|list<Repository>
     */
    public ?array $repositories {
        get => $this->repositories ??= $this->data['maybeViewer'] !== null ? array_map(fn($item) => new Repository($item), $this->data['maybeViewer']['repositories']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'maybeViewer': null|array{
     *         'account': null|array{
     *             'email': string,
     *             'settings': null|array{
     *                 'locale': string,
     *                 ...,
     *             },
     *             ...,
     *         },
     *         'bio': null|string,
     *         'project': null|array{
     *             'id': string,
     *             'name': string,
     *             ...,
     *         },
     *         'repositories': list<array{
     *             'name': string,
     *             'owner': array{
     *                 'ownerName': string,
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
