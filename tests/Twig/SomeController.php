<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Query\Projectsd4cba6\ProjectsQuery;
use Twig\Environment;

final readonly class SomeController
{
    private const string OPERATION = <<<'GRAPHQL'
        query Projects {
            ...AdminProjectList
        }
        GRAPHQL;

    public function __construct(
        private Environment $twig,
        #[GeneratedGraphQLClient(self::OPERATION)]
        public ProjectsQuery $query,
    ) {}

    public function __invoke() : string
    {
        return $this->twig->render(
            'list.html.twig',
            [
                'data' => $this->query->executeOrThrow()->adminProjectList,
            ],
        );
    }
}
