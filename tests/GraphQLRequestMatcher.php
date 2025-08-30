<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Http\Message\RequestMatcher as RequestMatcherInterface;
use Http\Message\RequestMatcher\RequestMatcher;
use Override;
use Psr\Http\Message\RequestInterface;

final readonly class GraphQLRequestMatcher implements RequestMatcherInterface
{
    private RequestMatcher $matcher;

    /**
     * The regular expressions used for path or host must be specified without delimiter.
     * You do not need to escape the forward slash / to match it.
     *
     * @param null|array<string, mixed> $variables Variables to match
     * @param null|string $path Regular expression for the path
     * @param null|string $host Regular expression for the hostname
     * @param null|string|list<string> $methods Method or list of methods to match
     * @param null|string|list<string> $schemes Scheme or list of schemes to match (e.g. http or https)
     */
    public function __construct(
        private ?array $variables = null,
        private ?string $operationName = null,
        ?string $path = null,
        ?string $host = null,
        null | array | string $methods = ['POST'],
        null | array | string $schemes = [],
    ) {
        $this->matcher = new RequestMatcher(
            $path,
            $host,
            $methods,
            $schemes,
        );
    }

    #[Override]
    public function matches(RequestInterface $request) : bool
    {
        if ( ! $this->matcher->matches($request)) {
            return false;
        }

        if ( ! str_starts_with($request->getHeaderLine('Content-Type'), 'application/json')) {
            return false;
        }

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        if ( ! is_array($body) || ! isset($body['operationName']) || ! is_string($body['operationName'])) {
            return false;
        }

        if ($this->operationName !== null && $body['operationName'] !== $this->operationName) {
            return false;
        }

        if ($this->variables !== null && array_key_exists('variables', $body) && $body['variables'] !== $this->variables) {
            return false;
        }

        return true;
    }
}
