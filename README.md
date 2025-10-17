<p align="center">
    <strong>GraphQL Client Code Generator for PHP</strong><br>
    <em>Transform your GraphQL queries into type-safe, zero-dependency PHP 8.4+ code. Let the generator handle types, validation, and boilerplate—you just write queries.</em>
</p>
<p align="center">
    <a href="https://packagist.org/packages/ruudk/graphql-client-code-generator"><img src="https://poser.pugx.org/ruudk/graphql-client-code-generator/v?style=for-the-badge" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/ruudk/graphql-client-code-generator"><img src="https://poser.pugx.org/ruudk/graphql-client-code-generator/require/php?style=for-the-badge" alt="PHP Version Require"></a>
    <a href="https://packagist.org/packages/ruudk/graphql-client-code-generator"><img src="https://poser.pugx.org/ruudk/graphql-client-code-generator/downloads?style=for-the-badge" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/ruudk/graphql-client-code-generator"><img src="https://poser.pugx.org/ruudk/graphql-client-code-generator/license?style=for-the-badge" alt="License"></a>
</p>

------

## Why This Library?

Ever struggled with GraphQL in PHP? Tired of wrestling with nested arrays, missing autocomplete, and runtime errors from typos?
Generic GraphQL clients force you to work with untyped arrays—your IDE can't help you, PHPStan can't verify anything, and every
query response is a mystery until runtime.

**This library changes that.**

Write a GraphQL query, run the generator, and get beautiful, type-safe PHP classes with zero runtime dependencies. Your IDE
autocompletes field names, PHPStan verifies everything at level 9, and bugs are caught during development—not production.

## The Problem

### ❌ Before: Array Hell

```php
// Generic GraphQL client - no types, no safety
$data = $client->query(<<<'GRAPHQL'
    query {
        repository(owner: "ruudk", name: "code-generator") {
            issues(first: 10) {
                nodes {
                    title
                    number
                    author { login }
                }
            }
        }
    }
    GRAPHQL
);

// What fields exist? Who knows! 🤷
$title = $data['data']['repository']['issues']['nodes'][0]['title'] ?? null;
//       ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//       No autocomplete, no type checking, runtime errors waiting to happen

// Did you make a typo? You'll find out in production! 💥
$author = $data['data']['repository']['issues']['nodes'][0]['autor']['login'];
//                                                              ^^^^^ typo!
```

### ✅ After: Type-Safe Bliss

<!-- source: examples/run.php -->
```php
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
```

## Installation

```bash
composer require --dev ruudk/graphql-client-code-generator
```

## Quick Start

**1. Create a config file:**

<!-- source: examples/config.php -->
```php
<?php

declare(strict_types=1);

use Http\Discovery\Psr18ClientDiscovery;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;
use Symfony\Component\Dotenv\Dotenv;
use Webmozart\Assert\Assert;

return Config::create(
    // https://docs.github.com/public/fpt/schema.docs.graphql
    schema: __DIR__ . '/schema.docs.graphql',
    projectDir: __DIR__,
    queriesDir: __DIR__,
    outputDir: __DIR__ . '/Generated',
    namespace: 'Ruudk\GraphQLCodeGenerator\Examples\Generated',
    client: GitHubClient::class,
)
    ->withIntrospectionClient(function () {
        $dotenv = new Dotenv();
        $dotenv->bootEnv(__DIR__ . '/.env.local');

        Assert::keyExists($_ENV, 'GITHUB_TOKEN');
        $token = $_ENV['GITHUB_TOKEN'];
        Assert::stringNotEmpty($token);

        return new GitHubClient(Psr18ClientDiscovery::find(), $token);
    })
    ->enableDumpDefinition()
    ->enableUseNodeNameForEdgeNodes()
    ->enableUseConnectionNameForConnections()
    ->enableUseEdgeNameForEdges();
```

**2. Write your GraphQL queries:**

<!-- source: examples/Search.graphql -->
```graphql
query Search {
    search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
        nodes {
            __typename
            ... on Issue {
                number
                title
            }
            ...PullRequestInfo
        }
    }
}

fragment PullRequestInfo on PullRequest {
    number
    title
    merged
}
```

**3. Generate type-safe PHP code:**

```bash
vendor/bin/graphql-client-code-generator
```

**4. Use it in your code:**

<!-- source: examples/run.php -->
```php
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
```

That's it! Your GraphQL queries are now type-safe PHP classes.

## What Makes This Awesome?

### 🎯 Zero Runtime Dependencies
The generated code has **no dependencies**. None. Only the generator tool needs libraries—your production code stays lean and lightning fast.

<!-- source: examples/Generated/Query/Search/SearchQuery.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search;

use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;

// This file was automatically generated and should not be edited.

final readonly class SearchQuery {
    public const string OPERATION_NAME = 'Search';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Search {
          search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
            nodes {
              __typename
              ... on Issue {
                number
                title
              }
              ...PullRequestInfo
            }
          }
        }
        
        fragment PullRequestInfo on PullRequest {
          number
          title
          merged
        }
        
        GRAPHQL;

    public function __construct(
        private GitHubClient $client,
    ) {}

    public function execute() : Data
    {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
```

### ✨ Beautiful Generated Code
Uses modern PHP 8.4 features like property hooks for lazy-loading nested objects:

<!-- source: examples/Generated/Query/Search/Data.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection;

// This file was automatically generated and should not be edited.

/**
 * query Search {
 *   search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
 *     nodes {
 *       __typename
 *       ... on Issue {
 *         number
 *         title
 *       }
 *       ...PullRequestInfo
 *     }
 *   }
 * }
 */
final class Data
{
    public SearchResultItemConnection $search {
        get => $this->search ??= new SearchResultItemConnection($this->data['search']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'search': array{
     *         'nodes': null|list<null|array{
     *             '__typename': string,
     *             'merged'?: bool,
     *             'number'?: int,
     *             'title'?: string,
     *         }>,
     *     },
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
```

### 🎭 Full Type Coverage
- **Object types** → Readonly classes with typed properties
- **Enums** → PHP 8.1+ backed enums (see example below)
- **Input types** → Constructor-validated classes
- **Fragments** → Encapsulated data classes
- **Unions & interfaces** → Proper type narrowing with inline fragments

<!-- source: examples/Generated/Enum/SearchType.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum SearchType: string
{
    // Returns matching discussions in repositories.
    case Discussion = 'DISCUSSION';

    // Returns results matching issues in repositories.
    case Issue = 'ISSUE';

    // Returns results matching issues in repositories.
    case IssueAdvanced = 'ISSUE_ADVANCED';

    // Returns results matching repositories.
    case Repository = 'REPOSITORY';

    // Returns results matching users and organizations on GitHub.
    case User = 'USER';
}
```

### 🚀 Smart Code Generation
- **Lazy-loading** for nested objects (only instantiated when accessed)
- **Connection pattern awareness** (edges, nodes, pageInfo) for Relay-style APIs
- **Custom `@indexBy` directive** for O(1) lookups instead of O(n) searching
- **Fragment dependency injection** - automatically includes required fragments
- **Automatic query optimization** - merges fragments, simplifies inline fragments

### 🔧 Flexible & Powerful
- **Multiple GraphQL APIs** in one project with different configs
- **Schema introspection** with auto-update from live endpoints
- **Custom scalar mapping** (DateTime, JSON, UUID, etc.)
- **Inline operations** - define GraphQL directly in PHP classes
- **Twig template extraction** - extract fragments from Twig files
- **Namespace customization** - organize generated code your way

### 🛡️ Quality & Validation
- **PHPStan Level 9** - strictest static analysis, zero compromises
- **Full GraphQL validation** against your schema
- **Unused fragment detection** - keeps your codebase clean
- **Query optimization passes** - automatic performance improvements
- **`--ensure-sync` flag** - verify generated code matches expectations in CI/CD

## Advanced Features

### 🎨 Inline GraphQL Operations

Define GraphQL directly in your PHP classes—the generator extracts, validates, and creates the query class for you.

**Perfect for Symfony autowiring:**

<!-- source: tests/Twig/SomeController.php -->
```php
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
```

**How it works:**

1. You define the GraphQL query inline with `#[GeneratedGraphQLClient(self::OPERATION)]`
2. Run the generator—it creates the `ProjectsQuery` class for you
3. Symfony autowires the query class into your constructor
4. Commit the generated code—now your CI can verify the query class exists and matches

**No separate `.graphql` files needed**—your GraphQL lives right next to where it's used, but you still get full type safety and validation!

### 🎭 Twig Template Support

Keep your GraphQL fragments next to where they're used in your templates:

<!-- source: tests/Twig/templates/_project_row.html.twig -->
```twig
{% types {
    project: '\\Ruudk\\GraphQLCodeGenerator\\Twig\\Generated\\Fragment\\AdminProjectRow',
} %}

{% graphql %}
fragment AdminProjectRow on Project {
    id
    name
    description
    ...AdminProjectOptions
}
{% endgraphql %}

<li>
    #{{ project.id }} - {{ project.name }}<br>
    {{ project.description }}
    <hr>
    {{ include('_project_options.html.twig', {project: project.adminProjectOptions}) }}
</li>
```

The generator extracts fragments from Twig files and creates type-safe classes. Your templates and GraphQL stay together!

### ⚡ Custom `@indexBy` Directive

Stop searching through arrays—index collections by a field for O(1) lookups:

<!-- source: tests/IndexByDirective/Test.graphql -->
```graphql
query Test {
    projects @indexBy(field: "id") {
        id
        name
    }
    issues @indexBy(field: "id") {
        id
        name
    }
    customers {
        edges @indexBy(field: "node.id") {
            node {
                id
                name
            }
        }
    }
}
```

**Generated code:**

<!-- source: tests/IndexByDirective/Generated/Query/Test/Data.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test\Data\CustomerConnection;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test\Data\Issue;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test\Data\Project;

// This file was automatically generated and should not be edited.

final class Data
{
    public CustomerConnection $customers {
        get => $this->customers ??= new CustomerConnection($this->data['customers']);
    }

    /**
     * @var array<int, Issue>
     */
    public array $issues {
        get => $this->issues ??= array_combine(
            array_column($this->data['issues'], 'id'),
            array_map(fn($item) => new Issue($item), $this->data['issues']),
        );
    }

    /**
     * @var array<string, Project>
     */
    public array $projects {
        get => $this->projects ??= array_combine(
            array_column($this->data['projects'], 'id'),
            array_map(fn($item) => new Project($item), $this->data['projects']),
        );
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'customers': array{
     *         'edges': list<array{
     *             'node': array{
     *                 'id': int,
     *                 'name': string,
     *             },
     *         }>,
     *     },
     *     'issues': list<array{
     *         'id': int,
     *         'name': string,
     *     }>,
     *     'projects': list<array{
     *         'id': string,
     *         'name': string,
     *     }>,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
```

## Configuration

The fluent configuration API gives you full control:

```php
<?php

use GraphQLClientCodeGenerator\Config\Config;

return Config::create('src/Generated/GraphQL')
    // 📄 Schema source (pick one)
    ->withSchema(__DIR__ . '/schema.graphql')  // Local file
    ->withSchemaIntrospection('https://api.github.com/graphql')  // Live endpoint

    // 🔍 Where to find operations
    ->withOperations(__DIR__ . '/queries')      // .graphql files
    ->withPhpOperations(__DIR__ . '/src')       // Inline PHP operations
    ->withTwigOperations(__DIR__ . '/templates') // Twig templates

    // 📦 Namespace & naming
    ->withNamespace('App\\Generated\\GitHub')

    // ⚙️ Customization
    ->withConnectionNaming()     // Use Connection/Edge/Node naming
    ->withAutoFormatting()       // Format generated .graphql files
    ->withCustomScalar('DateTime', 'DateTimeImmutable')
    ->withCustomScalar('JSON', 'array')

    // 🔌 Client integration
    ->withClientInterface('App\\GraphQL\\ClientInterface')
    ->withClientVariable('client');
```

## Requirements

- **PHP 8.4+** (uses property hooks, readonly classes, and other modern features)
- **Composer** for installation

## Philosophy

### Why Zero Dependencies?

Most GraphQL clients require runtime libraries to handle deserialization, validation, and type coercion. This adds dependencies, increases bundle size, and creates potential version conflicts.

**This generator takes a different approach:**

1. **Build-time analysis** - Analyzes your GraphQL schema and queries during code generation
2. **Plain PHP output** - Generates simple classes that work with arrays
3. **Zero runtime cost** - No libraries to load, no runtime parsing, just direct array access

The result? **Faster code, smaller bundles, and zero external dependencies in production.**

### How Fragments Work

**Named Fragments = Isolated Data Classes**

Named fragments become separate, reusable classes:

<!-- source: tests/Fragments/ProjectView.graphql -->
```graphql
fragment ProjectView on Project {
    name
    description
    ...ProjectStateView
}
```

**Fragment class:**

<!-- source: tests/Fragments/Generated/Fragment/ProjectView.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment;

// This file was automatically generated and should not be edited.

final class ProjectView
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ProjectStateView $projectStateView {
        get => $this->projectStateView ??= new ProjectStateView($this->data);
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'name': string,
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
```

**Used in query result:**

<!-- source: tests/Fragments/Generated/Query/Test/Data/Project.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\ProjectView;

// This file was automatically generated and should not be edited.

final class Project
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ProjectView $projectView {
        get => $this->projectView ??= new ProjectView($this->data);
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'name': string,
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
```

**Inline Fragments = Type Refinement**

Inline fragments narrow union/interface types:

<!-- source: examples/Search.graphql -->
```graphql
query Search {
    search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
        nodes {
            __typename
            ... on Issue {
                number
                title
            }
            ...PullRequestInfo
        }
    }
}

fragment PullRequestInfo on PullRequest {
    number
    title
    merged
}
```

Generates type-safe access:

<!-- source: examples/run.php -->
```php
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
```

### Static Analysis First

**If PHPStan Level 9 can't verify it, we don't generate it.**

Your IDE and static analysis tools catch errors during development—not in production. The generated code is explicit,
readable, and obvious. No magic, no hidden behavior, just straightforward PHP you can debug and understand.

## Testing & Validation

Run the generator's test suite:

```bash
vendor/bin/phpunit
```

### Committing Generated Code

**You should commit the generated code to your repository.** This means your CI/CD pipeline doesn't need to run the generator—the type-safe classes are already there, ready to use.

To ensure the committed code stays in sync with your queries and schema, add this to your CI:

```bash
vendor/bin/graphql-client-code-generator --ensure-sync
```

This validates that your committed generated code matches what the generator would produce. If someone updates a query but forgets to regenerate the code, CI catches it immediately.

**The workflow:**
1. Update your GraphQL queries or schema
2. Run `vendor/bin/graphql-client-code-generator` to regenerate
3. Commit both the queries and generated code
4. CI runs `--ensure-sync` to verify everything matches

## Examples

Check out the `examples/` directory for complete working examples:
- 🐙 **GitHub API integration** - Real-world queries with fragments
- 🎯 **Custom scalar handling** - DateTime, JSON, UUID mappings
- 🧩 **Fragment patterns** - Reusable fragments and composition
- 🔗 **Connection patterns** - Relay-style pagination
- ⚠️ **Error handling** - Type-safe GraphQL error handling

## Contributing

Contributions welcome! This project uses:
- **PHP-CS-Fixer** for code formatting
- **PHPStan** (level 9!) for static analysis
- **PHPUnit** for testing

Run the quality checks:

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan
vendor/bin/phpunit
```

## 💖 Support This Project

Love this tool? Help me keep building awesome open source software!

[![Sponsor](https://img.shields.io/badge/Sponsor-%E2%9D%A4-pink)](https://github.com/sponsors/ruudk)

Your sponsorship helps me dedicate more time to maintaining and improving this project. Every contribution, no matter the size, makes a difference!

## 🤝 Contributing

I welcome contributions! Whether it's a bug fix, new feature, or documentation improvement, I'd love to see your PRs.

## 📄 License

MIT License – Free to use in your projects! If you're using this and finding value, please consider [sponsoring](https://github.com/sponsors/ruudk) to support continued development.

