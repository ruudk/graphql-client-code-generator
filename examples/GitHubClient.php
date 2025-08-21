<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples;

use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use SensitiveParameter;
use Webmozart\Assert\Assert;

final readonly class GitHubClient
{
    public function __construct(
        private ClientInterface $client,
        #[SensitiveParameter]
        private string $token,
    ) {}

    /**
     * @param array<string, mixed> $variables
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws ClientExceptionInterface
     * @return array<mixed>
     */
    public function graphql(string $query, array $variables = [], ?string $operationName = null) : array
    {
        $request = new Request(
            'POST',
            'https://api.github.com/graphql',
            [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ],
            json_encode(array_filter([
                'query' => $query,
                'operationName' => $operationName,
                'variables' => $variables,
            ], fn($value) => ! is_null($value)), JSON_THROW_ON_ERROR),
        );

        $response = $this->client->sendRequest($request);

        Assert::same(200, $response->getStatusCode(), 'GraphQL server responded with a %2$s status code.');

        $data = json_decode((string) $response->getBody(), true, JSON_THROW_ON_ERROR);

        Assert::isArray($data, 'GraphQL server did not return an array.');

        return $data;
    }
}
