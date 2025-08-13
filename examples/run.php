<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Http\Discovery\Psr18ClientDiscovery;
use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\SearchQuery;
use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\ViewerQuery;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__ . '/.env.local');

$client = new GitHubClient(Psr18ClientDiscovery::find(), $_ENV['GITHUB_TOKEN']);

dump(new ViewerQuery($client)->execute()->viewer->login);

$data = new SearchQuery($client)->execute();

foreach ($data->search->nodes as $node) {
    if ($node->isIssue) {
        dump(asIssue: $node->asIssue->title);
    }

    if ($node->isPullRequestInfo) {
        dump(asPullRequest: $node->pullRequestInfo->title . ' is merged: ' . $node->pullRequestInfo->merged);
    }
}
