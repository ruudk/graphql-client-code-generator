<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Http\Discovery\Psr18ClientDiscovery;
use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\SearchQuery;
use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer\ViewerQuery;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;
use Symfony\Component\Dotenv\Dotenv;
use Webmozart\Assert\Assert;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__ . '/.env.local');

Assert::keyExists($_ENV, 'GITHUB_TOKEN');
$token = $_ENV['GITHUB_TOKEN'];
Assert::stringNotEmpty($token);

$client = new GitHubClient(Psr18ClientDiscovery::find(), $token);

dump(new ViewerQuery($client)->execute()->viewer->login);

$data = new SearchQuery($client)->execute();

foreach ($data->search->nodes ?? [] as $node) {
    if ($node === null) {
        continue;
    }

    if ($node->asIssue !== null) {
        dump(asIssue: $node->asIssue->title);
    }

    if ($node->pullRequestInfo !== null) {
        dump(asPullRequest: $node->pullRequestInfo->title . ' is merged: ' . $node->pullRequestInfo->merged);
    }
}
