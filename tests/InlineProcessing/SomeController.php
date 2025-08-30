<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\Inline1d8480Query;

final readonly class SomeController
{
    private const string OPERATION = <<<'GRAPHQL'
        query {
            viewer {
                login
                projects {
                    name
                    description
                }
            }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION)]
        public Inline1d8480Query $query,
    ) {}

    /**
     * @return array{string, string}
     */
    public function getResult() : array
    {
        $result = $this->query->execute();

        return [
            $result->viewer->login,
            $result->viewer->projects[0]->name,
        ];
    }
}
