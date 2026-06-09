<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Query\Test\Data\Article;
use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Query\Test\Data\Video;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Article>
     */
    public array $articles {
        get => $this->articles ??= array_map(fn($item) => new Article($item, $this->hooks), $this->data['articles']);
    }

    /**
     * @var list<Video>
     */
    public array $videos {
        get => $this->videos ??= array_map(fn($item) => new Video($item, $this->hooks), $this->data['videos']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'articles': list<array{
     *         'id': string,
     *         'title': string,
     *         ...,
     *     }>,
     *     'videos': list<array{
     *         'duration': int,
     *         'id': string,
     *         ...,
     *     }>,
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     * @param array{
     *     'findOwner': FindOwnerHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
