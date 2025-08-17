<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Expected\Fragment;

// This file was automatically generated and should not be edited.

/**
 * fragment ViewerDetails on Viewer {
 *   login
 * }
 */
final class ViewerDetails
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Viewer'];

    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    /**
     * @param array{
     *     'login': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
