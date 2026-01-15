<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\StaleImportRemoval;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewer6b9a6d\GetViewerQuery;

final readonly class ControllerWithStaleImport
{
    private const string OPERATION = <<<'GRAPHQL'
        query GetViewer {
            viewer {
                login
            }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION)]
        public GetViewerQuery $query,
    ) {}
}
