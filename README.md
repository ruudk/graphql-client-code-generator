<p align="center">
    <strong>GraphQL Client Code Generator for PHP</strong><br>
    <em>Transform your GraphQL queries into type-safe, zero-dependency PHP 8.5+ code. Let the generator handle types, validation, and boilerplate—you just write queries.</em>
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
        dump(
            asPullRequest: $node->pullRequestInfo->title . ' is merged: '
                . var_export($node->pullRequestInfo->merged, true),
        );
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
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;
use Symfony\Component\Dotenv\Dotenv;
use Webmozart\Assert\Assert;

return Config::create(projectDir: __DIR__)->withSchema(
    SchemaConfig::create(
        // https://docs.github.com/public/fpt/schema.docs.graphql
        schema: __DIR__ . '/schema.docs.graphql',
        outputDir: __DIR__ . '/Generated',
        namespace: 'Ruudk\GraphQLCodeGenerator\Examples\Generated',
        client: GitHubClient::class,
    )
        ->withQueriesDir(__DIR__)
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
        ->enableUseEdgeNameForEdges(),
);
```

`Config` describes your codebase: the project directory and the directories every schema reads. `SchemaConfig` describes one schema: where its SDL lives, where its code goes, which client runs it, and how to generate it. A config holds one or more schemas.

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
        dump(
            asPullRequest: $node->pullRequestInfo->title . ' is merged: '
                . var_export($node->pullRequestInfo->merged, true),
        );
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

    /**
     * @api
     */
    public function execute() : Data
    {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
            ],
            self::OPERATION_NAME,
        );

        // @mago-expect analysis:mixed-argument(2)
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
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
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

    // Returns results matching issues using hybrid (lexical + semantic) search.
    case IssueHybrid = 'ISSUE_HYBRID';

    // Returns results matching issues using semantic search.
    case IssueSemantic = 'ISSUE_SEMANTIC';

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
- **Custom `@groupBy` directive** - group a list by one or more fields, e.g. issues per assignee and state
- **Custom `@throwWhenNull` directive** - drop nullability per-field and throw a typed exception when the server returns null
- **Custom `@flatten` directive** - lift the fields of a nested object onto its parent class instead of generating a class for it
- **Custom `@narrow` directive** - type a field that returns an interface or union as one object type, and throw when the server returns another one
- **Custom `@hook` directive** - enrich responses with local data resolved lazily at access time
- **Operation arguments** via `withOperationArgument()` - inject extra typed parameters (e.g. an actor) into `execute()` and forward them to your client, optionally gated by a custom directive
- **Fragment dependency injection** - automatically includes required fragments
- **Automatic query optimization** - merges fragments, simplifies inline fragments

### 🔧 Flexible & Powerful
- **Multiple GraphQL APIs** in one project with different configs
- **Schema introspection** with auto-update from live endpoints
- **Custom scalar mapping** (DateTime, JSON, UUID, etc.), with an input type of its own when you need one
- **Inline operations** - define GraphQL directly in PHP classes
- **Inline fragments** - services publish their data contract via `#[GeneratedGraphQLFragment]`
- **Twig template extraction** - extract fragments from Twig files
- **Namespace customization** - organize generated code your way

### 🛡️ Quality & Validation
- **PHPStan Level 9** - strictest static analysis, zero compromises
- **Full GraphQL validation** against your schema
- **Unused fragment detection** - keeps your codebase clean
- **Schema deprecations carry over** - deprecated fields and enum values become `@deprecated` in the generated code
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

    public function __invoke(): string
    {
        return $this->twig->render('list.html.twig', [
            'data' => $this->query->executeOrThrow()->adminProjectList,
        ]);
    }
}
```

**How it works:**

1. You define the GraphQL query inline with `#[GeneratedGraphQLClient(self::OPERATION)]`
2. Run the generator—it creates the `ProjectsQuery` class for you
3. Symfony autowires the query class into your constructor
4. Commit the generated code—now your CI can verify the query class exists and matches

**No separate `.graphql` files needed**—your GraphQL lives right next to where it's used, but you still get full type safety and validation!

### 🧩 Inline GraphQL Fragments

A service that operates on a slice of data can **publish its own data contract** as a fragment via `#[GeneratedGraphQLFragment]`. Callers spread the fragment into their queries and hand the resulting typed object back to the service — without having to know which fields the service reads internally.

<!-- source: tests/InlineFragment/UserMapper.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLFragment;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Fragment\UserName56995d\UserName;

final readonly class UserMapper
{
    private const string FRAGMENT = <<<'GRAPHQL'
        fragment UserName on User {
          id
          firstName
          lastName
        }
        GRAPHQL;

    /**
     * @param list<UserName> $users
     * @return list<string>
     */
    public function mapMany(#[GeneratedGraphQLFragment(self::FRAGMENT)] array $users): array
    {
        return array_map(static fn(UserName $u) => $u->firstName . ' ' . $u->lastName, $users);
    }
}
```

**Multiple consumers spread the same fragment:**

<!-- source: tests/InlineFragment/ListUsersClient.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\ListUsers9908fe\ListUsersQuery;

final readonly class ListUsersClient
{
    private const string OPERATION = <<<'GRAPHQL'
        query ListUsers {
          users {
            ...UserName
          }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION)]
        private ListUsersQuery $query,
        private UserMapper $mapper,
    ) {}

    /**
     * @return list<string>
     */
    public function getDisplayNames(): array
    {
        return $this->mapper->mapMany(array_map(static fn($user) => $user->userName, $this->query->execute()->users));
    }
}
```

**How it works:**

1. The service tags a method parameter with `#[GeneratedGraphQLFragment(self::FRAGMENT)]`.
2. The generator emits the fragment class at `Generated/Fragment/{Name}{hash}/{Name}.php` — hashed by the declaration site, like `#[GeneratedGraphQLClient]`.
3. The fragment class is stamped with `#[Generated(source: …, restricted: true, restrictInstantiation: true)]`, so the [PHPStan extension](#-restrict-generated-classes) and the [Mago extension](#-mago-extension) only let the declaring service consume it.
4. Any query — `.graphql` file, Twig block, or another inline `#[GeneratedGraphQLClient]` — can spread `...UserName`; the generated Data class imports the same hashed fragment class.
5. Callers hand fragment-shaped data back to the service, which is the only place allowed to read it.

**Fragment names must be globally unique** across `.graphql` files, Twig templates, and PHP attributes — the generator throws on conflict, naming both source locations.

#### 🧹 Auto-Format GraphQL in PHP Heredocs

This library ships a formatter that keeps the GraphQL inside your `<<<'GRAPHQL' ... GRAPHQL` nowdoc strings consistently formatted. It parses the operation and rewrites it with `webonyx/graphql-php`'s `Printer::doPrint()`, indented to match the nowdoc. Only single-quoted nowdocs are touched (a heredoc would interpolate GraphQL `$variables` as PHP variables); invalid GraphQL is left untouched.

It is available for both [PHP CS Fixer](https://cs.symfony.com/) and [Mago](https://mago.carthage.software/).

**PHP CS Fixer:** register `GraphQLHeredocFixer` in your `.php-cs-fixer.php`:

<!-- source: examples/Tools/php-cs-fixer.php -->
```php
<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use Ruudk\GraphQLCodeGenerator\PhpCsFixer\GraphQLHeredocFixer;

return new Config()
    ->setFinder(Finder::create()->in(__DIR__ . '/src'))
    ->registerCustomFixers([new GraphQLHeredocFixer()])
    ->setRules([
        '@PER-CS' => true,
        'Ruudk/graphql_heredoc' => true,
    ]);
```

Running `vendor/bin/php-cs-fixer fix` now formats every `<<<'GRAPHQL'` block automatically. The fixer runs before `heredoc_indentation`, so the surrounding indentation stays consistent with the rest of your codebase.

**Mago:** enable the [Mago extension](#-mago-extension); its `graphql/heredoc-format` lint rule reports unformatted nowdocs and `vendor/bin/mago lint --fix` formats them. The GraphQL is indented to the closing `GRAPHQL` marker, so it works together with Mago's formatter.

### 🗂️ Several Schemas in One Codebase

Talking to more than one GraphQL API? Add a `SchemaConfig` per schema to one `Config`. Each schema has its own SDL, client, namespace and output directory, and once there is more than one, each needs a `name`.

Directories on the `Config` are read by every schema. Directories on a `SchemaConfig` are read by that schema only:

```php
return Config::create(projectDir: __DIR__)
    // Read by every schema
    ->withInlineProcessingDirectory(__DIR__ . '/src/Checkout')
    ->withSchema(
        SchemaConfig::create(
            schema: __DIR__ . '/billing.graphql',
            outputDir: __DIR__ . '/src/Generated/Billing',
            namespace: 'App\\Generated\\Billing',
            client: BillingClient::class,
            name: 'billing',
        )
            // Read by the billing schema only
            ->withInlineProcessingDirectory(__DIR__ . '/src/Billing'),
    )
    ->withSchema(
        SchemaConfig::create(
            schema: __DIR__ . '/shipping.graphql',
            outputDir: __DIR__ . '/src/Generated/Shipping',
            namespace: 'App\\Generated\\Shipping',
            client: ShippingClient::class,
            name: 'shipping',
        )->withInlineProcessingDirectory(__DIR__ . '/src/Shipping'),
    );
```

Whether an attribute has to name its schema depends on the file it is in:

- **One schema reads the file** (`src/Billing`): leave the schema out, like with a single schema.
- **More than one schema reads the file** (`src/Checkout`): name the schema with `schema:`. Leaving it out, or naming a schema that does not read the file, fails the run with the location of the attribute.

<!-- source: tests/Schemas/Checkout/CheckoutController.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Checkout;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\CheckoutViewer0d4a41\CheckoutViewerQuery;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\CheckoutParcelsaa940f\CheckoutParcelsQuery;

/**
 * Both schemas read this directory, so every attribute names its schema.
 */
final readonly class CheckoutController
{
    private const string BILLING = 'billing';
    private const string VIEWER = <<<'GRAPHQL'
        query CheckoutViewer {
          viewer {
            login
          }
        }
        GRAPHQL;
    private const string PARCELS = <<<'GRAPHQL'
        query CheckoutParcels {
          parcels {
            trackingCode
          }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::VIEWER, self::BILLING)]
        public CheckoutViewerQuery $viewer,
        #[GeneratedGraphQLClient(self::PARCELS, schema: 'shipping')]
        public CheckoutParcelsQuery $parcels,
    ) {}
}
```

The `schema` argument is a string or a string constant of the same class, because the generator reads it without loading your code. One class can hold clients of different schemas, and moving a client to another schema removes the import of its old class.

Twig templates follow the same rule. Directories added with `withTwigProcessingDirectory()` on the `Config` are read by every schema, and a `{% graphql %}` block in them names its schema:

<!-- source: tests/Schemas/templates/_invoice_row.html.twig -->
```twig
{# Both schemas read this directory, so the block names its schema. #}
{% graphql schema 'billing' %}
fragment InvoiceRow on Invoice {
  id
  total
}
{% endgraphql %}

<li>#{{ invoice.id }}: {{ invoice.total }}</li>
```

All schemas that read the same directories have to be in the same `Config`. Passing several config files that read the same directory fails, because only the schemas of one config know about each other.

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

Add the directories that hold your templates with `withTwigProcessingDirectory()`. When more than one schema reads a template, name the schema of each block with `{% graphql schema 'billing' %}`, as described in *Several Schemas in One Codebase* above.

#### 🧹 Auto-Format GraphQL in Twig

A [Twig CS Fixer](https://github.com/VincentLanglet/Twig-CS-Fixer) rule ships with this library to keep the GraphQL inside your `{% graphql %}` blocks consistently formatted. It parses the operation and rewrites it with `webonyx/graphql-php`'s `Printer::doPrint()`, re-indented to match the block. Invalid GraphQL is reported as an error and left untouched, and blocks that embed Twig are skipped.

Register `GraphQLFormatterRule` (and the `GraphQLTokenParser` so Twig CS Fixer understands the tag) in your `.twig-cs-fixer.php`:

<!-- source: examples/Tools/twig-cs-fixer.php -->
```php
<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Twig\GraphQLFormatterRule;
use Ruudk\GraphQLCodeGenerator\Twig\GraphQLTokenParser;
use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());
$ruleset->addRule(new GraphQLFormatterRule());

$config = new Config();
$config->allowNonFixableRules();
$config->setRuleset($ruleset);
$config->setFinder(Finder::create()->in(__DIR__ . '/templates'));
$config->addTokenParser(new GraphQLTokenParser());

return $config;
```

Running `vendor/bin/twig-cs-fixer fix` now formats every `{% graphql %}` block automatically, and `vendor/bin/twig-cs-fixer lint` fails on any block that isn't canonically formatted.

### 🔒 Restrict Generated Classes

Generated classes marked `#[Generated(restricted: true)]` may only be used by the class (or Twig template) they were generated for. Two static analysis integrations enforce this.

**PHPStan:** with [phpstan/extension-installer](https://github.com/phpstan/extension-installer) the extension is registered automatically. Point it at your configs in `phpstan.neon`:

```neon
parameters:
    graphQLClientCodeGenerator:
        configs:
            - graphql.php
```

**Mago:** enable the analyzer plugin of the [Mago extension](#-mago-extension) by passing your configs to `GraphQLExtension::create()`. It reports `graphql/restricted-class`, `graphql/restricted-instantiation`, `graphql/restricted-method` and `graphql/restricted-property`.

### 🪦 Deprecated Fields and Enum Values

When the schema marks a field or enum value with `@deprecated`, the generated property or enum case
gets a `@deprecated` tag with the reason. Mago, PHPStan and your IDE then point at every place in
your code that still reads it, so you find what to migrate before the server removes it.

```graphql
type User {
    login: String! @deprecated(reason: "Use `name` instead.")
}
```

```php
/**
 * @deprecated Use `name` instead.
 */
public string $login {
    /**
     * @mago-expect analysis:deprecated-property
     */
    get => $this->login ??= $this->data['login'];
}
```

This also works for aliased fields, `@throwWhenNull` fields, their `...OrThrow` properties and
fields lifted by `@flatten` (a deprecated flattened field marks every property it lifts).

The generated getter reads its own deprecated property. So that only reads in your own code get
reported:

- **Mago:** the `@mago-expect` on the getter covers that read.
- **PHPStan** with [phpstan/phpstan-deprecation-rules](https://github.com/phpstan/phpstan-deprecation-rules):
  the [PHPStan extension](#-restrict-generated-classes) ignores reads of a deprecated property inside
  generated classes. Point it at your configs, as shown there.

### 🪄 Mago Extension

The library ships a [Mago](https://mago.carthage.software/) extension (Mago 1.61 or newer) with:

- the `graphql/heredoc-format` lint rule, see [Auto-Format GraphQL in PHP Heredocs](#-auto-format-graphql-in-php-heredocs);
- the `graphql` analyzer plugin, see [Restrict Generated Classes](#-restrict-generated-classes). It is only enabled when you pass the configs of the code generator.

Install Mago with Composer, which also brings the SDK the extension is built on:

```bash
composer require --dev carthage-software/mago
```

Mago extensions run in a PHP worker process that your project owns. Create a worker entrypoint, for example `.mago/extensions.php`:

```php
<?php

declare(strict_types=1);

use Mago\Sdk\Worker;
use Ruudk\GraphQLCodeGenerator\Mago\GraphQLExtension;

require dirname(__DIR__) . '/vendor/autoload.php';

new Worker(GraphQLExtension::create([
    dirname(__DIR__) . '/graphql.php',
]))->run();
```

Then register it in `mago.toml`:

```toml
[extension-hosts.graphql]
command = ["php", ".mago/extensions.php"]
```

Check that Mago picks it up with `vendor/bin/mago extension list`. Twig templates are not covered yet: Mago extensions cannot add Twig lint rules ([carthage-software/mago#2813](https://github.com/carthage-software/mago/issues/2813)), so keep using the Twig CS Fixer rule for `{% graphql %}` blocks.

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
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     'issues': list<array{
     *         'id': int,
     *         'name': string,
     *         ...,
     *     }>,
     *     'projects': list<array{
     *         'id': string,
     *         'name': string,
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
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
```

#### Index by several fields

Give a list of fields to nest the arrays, one level per field in the order given:

<!-- source: tests/IndexByDirective/TestMultiField.graphql -->
```graphql
query TestMultiField {
    projects @indexBy(field: ["id", "name"]) {
        id
        name
    }
    issues @indexBy(field: "id") {
        id
        name
    }
    customers {
        edges @indexBy(field: ["node.id", "node.name"]) {
            node {
                id
                name
            }
        }
    }
}
```

`projects` becomes `array<string, array<string, Project>>`, keyed by `id` and then by `name`. A
single field can be written as `field: "id"` or `field: ["id"]`, both mean the same. On connection
edges indexed by several fields, `nodes` is a flat list.

#### Index through a nullable field

Every field in the path must be non-null, so the key is never `null`. A field that is nullable in
the schema counts as non-null when you select it with
[`@throwWhenNull`](#-throw-on-null-with-throwwhennull-directive), directly or in a fragment:

```graphql
query Payouts {
    payouts @indexBy(field: "orderItem.id") {
        id
        orderItem @throwWhenNull {
            id
        }
    }
}
```

`payouts` becomes `array<string, Payout>`. When an item has no `orderItem`, building the array
throws `NodeNotFoundException` (`Field Payout.orderItem is null`), the same as the `orderItem`
getter. This works for every field in the path, the key itself included, and for every field when
indexing by several fields. A nullable field without `@throwWhenNull` is still an error.

### 🗃️ Group Lists with `@groupBy` Directive

Where `@indexBy` expects one item per key, `@groupBy` collects every item that shares a key into a
list. Enable it with `enableGroupByDirective()` and tag a list:

<!-- source: tests/GroupByDirective/Test.graphql -->
```graphql
query Test {
    issuesByState: issues @groupBy(field: "state") {
        id
        title
        state
    }
    issuesByProject: issues @groupBy(field: "project.id") {
        id
        title
        project {
            id
            name
        }
    }
    customers {
        edges @groupBy(field: "node.country") {
            node {
                id
                name
                country
            }
        }
    }
}
```

The property becomes `array<key, list<Item>>`:

```php
foreach ($result->issuesByState['OPEN'] ?? [] as $issue) {
    echo $issue->title;
}
```

- The key is the raw value from the response: an enum is grouped by its string value
  (`'OPEN'`), a custom scalar by its JSON value.
- Groups keep the order in which their key first appears, and items keep their order within a group.
- The field must be a selected, non-null `String`, `Int`, `ID` or enum, like with `@indexBy`.
  Nested paths such as `project.id` work.
- A field in the path that is nullable in the schema counts as non-null when you select it with
  `@throwWhenNull`, [like with `@indexBy`](#index-through-a-nullable-field): an item where it is
  `null` makes building the groups throw `NodeNotFoundException`.
- On grouped connection edges, `nodes` has the same groups.
- `@groupBy` and `@indexBy` can't be combined on one field.

#### Group by several fields

Give a list of fields to nest the groups, one level per field in the order given. A single field
can be written as `field: "state"` or `field: ["state"]`, both mean the same:

<!-- source: tests/GroupByDirective/TestMultiField.graphql -->
```graphql
query TestMultiField {
    issuesByAssigneeAndState: issues @groupBy(field: ["assignee.id", "state"]) {
        id
        title
        state
        assignee {
            id
        }
    }
    customers {
        edges @groupBy(field: ["node.tier", "node.country"]) {
            node {
                id
                name
                tier
                country
            }
        }
    }
}
```

With `AccountId` as a custom scalar whose JSON value is an `int`, `issuesByAssigneeAndState` becomes
`array<int, array<string, list<IssuesByAssigneeAndState>>>`: first by assignee, then by the string
value of the state. On the grouped edges, `nodes` gets the same nested shape as `edges`:

```php
foreach ($result->issuesByAssigneeAndState as $assigneeId => $issuesByState) {
    foreach ($issuesByState['OPEN'] ?? [] as $issue) {
        echo $issue->title;
    }
}
```

Every level follows the rules above: each field must pass the same checks, every key is the raw
value from the response, and each level keeps the order in which its keys first appear. Only the
innermost level is a list.

The directive is local to your query: it is stripped from the operation sent to the server.

### 💥 Throw on Null with `@throwWhenNull` Directive

Some nullable fields are nullable only for legacy or convenience reasons — at the point you read
them, you know they must be set, and you'd rather get a typed exception than a `TypeError` deep in
your code.

Enable the directive once with `enableThrowWhenNullDirective()`, then tag a selection with
`@throwWhenNull` and the generated property loses its nullability and throws
`NodeNotFoundException` if the server returns `null` for that field. Works on scalars and objects.
The directive is local to your query — it is stripped from the operation sent to the server and
does not need to be defined in your schema. It is independent from `enableDumpOrThrowProperties()`:
the exception class is generated whenever any field uses the directive.

<!-- source: tests/ThrowWhenNullDirective/Test.graphql -->
```graphql
query Test {
    viewer @throwWhenNull {
        login
        description @throwWhenNull
        project @throwWhenNull {
            id
            name
        }
    }
}
```

**Generated code:**

<!-- source: tests/ThrowWhenNullDirective/Generated/Query/Test/Data/Viewer.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\Query\Test\Data\Viewer\Project;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public string $description {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : throw NodeNotFoundException::create('Viewer', 'description');
    }

    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    public Project $project {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->project ??= $this->data['project'] !== null ? new Project($this->data['project']) : throw NodeNotFoundException::create('Viewer', 'project');
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'login': string,
     *     'project': null|array{
     *         'id': string,
     *         'name': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
```

Applying the directive to a non-nullable schema field raises a planning error — the directive
exists to express intent that the server may return null but the consumer requires a value.

It also works on a [`@hook`](#-local-resolution-with-hook-directive) field whose hook returns a
nullable type: the property drops the `null`, and reading it throws `NodeNotFoundException` when
the hook (batched or not) returns `null`. A hook whose return type is already non-nullable raises
a planning error, just like a non-nullable schema field.

### 🫓 Flatten Nested Objects with `@flatten` Directive

Some objects only exist in the schema to group a few fields, and walking through them in your
code adds nothing: `$data->viewer->firstName`, `$user->account->settings->locale`. Tag such a
field with `@flatten` and its selected fields become properties of the class that selects it.
No class is generated for the flattened object.

Enable the directive once with `enableFlattenDirective()`. Like `@throwWhenNull`, it is local to
your query: it is stripped from the operation sent to the server, which still returns the nested
object. Only the generated properties change.

<!-- source: tests/FlattenDirective/Basic.graphql -->
```graphql
query Basic {
    viewer @flatten {
        firstName
        lastName
    }
}
```

**Generated code:**

<!-- source: tests/FlattenDirective/Generated/Query/Basic/Data.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Basic;

// This file was automatically generated and should not be edited.

final class Data
{
    public string $firstName {
        get => $this->firstName ??= $this->data['viewer']['firstName'];
    }

    public string $lastName {
        get => $this->lastName ??= $this->data['viewer']['lastName'];
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'viewer': array{
     *         'firstName': string,
     *         'lastName': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
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

How it behaves:

- **Nesting:** `@flatten` can be chained (`viewer @flatten { account @flatten { email } }` gives
  `$data->email`). A nested object that is not flattened keeps its own class, which moves up to
  the class that now exposes it.
- **Nullability:** when a flattened object is nullable, every lifted property becomes nullable.
  Combine it with `@throwWhenNull` (`viewer @flatten @throwWhenNull { ... }`) to keep the
  properties non-null and throw `NodeNotFoundException` when the object is null.
- **Name clashes:** a lifted field may not share its name with another property of the class.
  Rename one of them with an alias (`viewerName: name`).
- **Fragment spreads** inside a flattened object become fragment accessors on the parent class.

`@flatten` only works on a single object. Lists, interfaces and unions, object types mapped with
`withObjectType()`, and `@include`, `@skip`, `@hook` or inline fragments inside the flattened
object are rejected while generating.

### 🎯 Narrow Interfaces and Unions with `@narrow` Directive

A field that returns an interface or union, like Relay's `node(id:)`, gives you a class with a
nullable `as<Type>` accessor per inline fragment. When you know which type you asked for, that
is two null checks too many: `$data->event?->asEvent?->types`.

Enable the directive once with `enableNarrowDirective()`, then tag the inline fragment with
`@narrow`. The field is typed as the fragment's object type and the fields selected inside the
fragment become properties of the field's class: `$data->event->types`. Reading the field throws
`NodeNotFoundException` when the server returns another type. Like the other directives, it is
local to your query and stripped from the operation sent to the server.

<!-- source: tests/NarrowDirective/ListTicketTypes.graphql -->
```graphql
query ListTicketTypes($id: ID!, $first: Int!, $after: String) {
    event: node(id: $id) @throwWhenNull {
        __typename
        id
        ... on Event @narrow {
            name
            types(first: $first, after: $after) {
                edges {
                    cursor
                    node {
                        id
                        title
                    }
                }
            }
        }
    }
}
```

**Generated code:**

<!-- source: tests/NarrowDirective/Generated/Query/ListTicketTypes/Data/Event.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data\Event\Types;

// This file was automatically generated and should not be edited.

final class Event
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public Types $types {
        get => $this->types ??= new Types($this->data['types']);
    }

    /**
     * @var array{
     *     '__typename': string,
     *     'id': string,
     *     'name': string,
     *     'types': array{
     *         'edges': list<array{
     *             'cursor': string,
     *             'node': array{
     *                 'id': string,
     *                 'title': string,
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     ...,
     * }
     */
    private readonly array $data;

    /**
     * @param array{
     *     '__typename': string,
     *     'id': string,
     *     'name'?: string,
     *     'types'?: array{
     *         'edges': list<array{
     *             'cursor': string,
     *             'node': array{
     *                 'id': string,
     *                 'title': string,
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @throws NodeNotFoundException
     */
    public function __construct(
        array $data,
    ) {
        if ($data['__typename'] !== 'Event' || ! array_key_exists('name', $data) || ! array_key_exists('types', $data)) {
            throw NodeNotFoundException::unexpectedType('Query', 'event', 'Event', $data['__typename']);
        }

        $this->data = $data;
    }
}
```

How it behaves:

- **Nullability:** `@narrow` only decides the type. A nullable field stays nullable; combine it
  with `@throwWhenNull` on the field to drop that too, as in the example above.
- **Lists:** on a list (`nodes(ids: $ids) { ... on Event @narrow { name } }`) every item is
  narrowed, and reading the list throws when one of them is another type.
- **Other selections:** fields and fragment spreads next to the inline fragment end up on the
  same class.

`@narrow` must sit on an inline fragment directly inside a field that returns an interface or
union, name an object type, and be the only inline fragment of that field. Fragment spreads on
another object type, and `@include` or `@skip` on the narrowed fragment, are rejected while
generating.

### 🪝 Local Resolution with `@hook` Directive

Enrich query results with data that does not come from the GraphQL server.
Common case: the backend returns an ID, and you want the generated response to also expose the
fully hydrated local entity (from your database, cache, etc.) lazily on first access.

A hook is **self-describing**: it declares the data it needs as a GraphQL fragment, and the
generator injects that selection into your queries automatically. The call site is just
`@hook(name: ...)` — it never has to know what the hook reads internally.

**Step 1 — write an invokable hook class.** Tag it with `#[Hook(name: ..., requires: ...)]`,
where `requires` is a named GraphQL fragment describing the data the hook needs:

```php
namespace App\Hooks;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Generated\Hook\ProjectCreator;
use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

#[Hook(
    name: 'findUserById',
    requires: <<<'GRAPHQL'
        fragment ProjectCreator on Project {
          creator {
            id
          }
        }
        GRAPHQL,
)]
final readonly class FindUserByIdHook
{
    public function __construct(private UserRepository $users) {}

    public function __invoke(ProjectCreator $project): ?User
    {
        return $this->users->find($project->creator->id);
    }
}
```

The fragment's **name** (`ProjectCreator`) becomes the generated data class the hook receives —
emitted into `Generated/Hook/`, read through typed properties (so custom scalars arrive fully
instantiated). The fragment's **type condition** (`on Project`) is the type a `@hook` field may
be attached to; it may be an interface, in which case the hook works on any implementer. The
hook's return type is inferred from `__invoke`.

**Step 2 — register the hook** in your config:

```php
SchemaConfig::create(/* ... */)
    ->withHook(App\Hooks\FindUserByIdHook::class);
```

**Step 3 — use `@hook` in your query** — no input list, just the name:

```graphql
query Project {
    project(id: "42") {
        name

        # Synthetic field populated by the hook. The fields the hook needs
        # (creator { id }) are injected into the query automatically.
        user @hook(name: "findUserById")
    }
}
```

The `user` field doesn't exist in the schema — it's a generator-only marker. Before the
operation is sent to the server the generator substitutes it with the hook's `requires`
selection, merged with whatever the caller already selected (no duplicate network fields).
Fields the hook needs but the caller didn't ask for stay invisible on the caller's generated
classes — they only feed the hook's data object.

**Step 4 — pass hook instances when executing**:

```php
$project = new ProjectQuery($client, [
    'findUserById' => new FindUserByIdHook($userRepository),
])->execute()->project;

$project->user; // ?User, resolved lazily by the hook on first access
```

**Require a value with `@throwWhenNull`.** When the hook returns a nullable type but you need a
value, add [`@throwWhenNull`](#-throw-on-null-with-throwwhennull-directive) (enabled with
`enableThrowWhenNullDirective()`) to the hook field. The property becomes non-nullable, and
reading it throws `NodeNotFoundException` when the hook returns `null`. This works for batched
hooks too. On a hook whose return type is already non-nullable the directive raises a planning
error.

<!-- source: tests/HooksThrowWhenNull/Test.graphql -->
```graphql
query Test {
    customers {
        id

        # Hook invoked once per customer; returns ?User.
        user @hook(name: "findUserById") @throwWhenNull

        # Batched hook; resolves ?Account for every customer in one call.
        account @hook(name: "findAccountById") @throwWhenNull
    }
}
```

```php
$customer->user; // User, or NodeNotFoundException when the hook returns null
```

**Symfony autowire shortcut.** Call `enableSymfonyAutowireHooks()` on the config and the
generated query class's `$hooks` parameter is annotated with `#[Autowire([...])]`, so the DI
container wires the map automatically:

```php
// Generated ProjectQuery.php
public function __construct(
    private TestClient $client,
    #[Autowire([
        'findUserById' => new Autowire(FindUserByIdHook::class)
    ])]
    private array $hooks,
) {}
```

#### ⚡ Batched Hooks (Hook Loaders)

By default a hook is resolved **once per object instance**. When a hooked field lives in a
list — or a list nested in a list — the hook fires once per element: a classic N+1.

Opt into **batching** with `batched: true` on the `#[Hook]` attribute. A batched hook is
invoked **exactly once per operation**, lazily, on first access of any hooked property. The
generator emits a `HookLoader` (into your `Generated/` namespace, zero dependencies) that
walks the typed result graph once, collects every occurrence's data object, and calls the hook
a single time with the whole batch.

The `__invoke` signature changes: a batched hook receives **one array of data objects** and
returns/yields the results, echoing back the integer keys it was given:

```php
#[Hook(
    name: 'findUserById',
    requires: <<<'GRAPHQL'
        fragment ProjectCreator on Project {
          creator { id }
        }
        GRAPHQL,
    batched: true,
)]
final class FindUserByIdHook
{
    public function __construct(private UserRepository $users) {}

    /**
     * @param array<int, ProjectCreator> $inputs   one per occurrence
     * @return iterable<int, ?User>                 echo the same integer keys
     */
    public function __invoke(array $inputs): iterable
    {
        foreach ($inputs as $key => $project) {
            yield $key => $this->users->find($project->creator->id);
        }
    }
}
```

The query is unchanged — the same `@hook(name: ...)` directive drives both modes. The hook's
return value type is inferred from the `iterable<int, V>` it yields; declare
`@return iterable<int, ...>` so the generator can read it. Omitting `batched` (or
`batched: false`) keeps the per-instance behaviour; legacy and batched hooks can be mixed in
one query.

### 🎭 Inject Extra Arguments with `withOperationArgument()`

Sometimes a generated operation needs to forward something to your client that is *not* a GraphQL
variable — an actor, a tenant, a correlation id, an idempotency key. `withOperationArgument()`
prepends an extra typed parameter to the generated `execute()` / `executeOrThrow()` methods and
forwards it positionally to your client's `graphql()` call (right after the operation name):

```php
use Symfony\Component\TypeInfo\Type;

SchemaConfig::create(/* ... */)
    ->withOperationArgument(
        name: 'actor',
        type: Type::object(Actor::class),
        directive: 'requiresActor', // opt-in: only operations tagged @requiresActor
    );
```

- **Opt-in via a directive** — pass `directive: 'requiresActor'` and only operations carrying
  `@requiresActor` receive the extra parameter. The directive is local to your operation: it is
  registered automatically (so validation passes) and stripped from the query sent to the server.
- **Apply to every operation** — omit `directive` and the parameter is injected into *all*
  operations.
- **Restrict by operation type** — pass `operations: ['mutation']` (or `['query']`) to limit which
  operation types the argument may target. The default is an empty array, which allows any type.
- Call `withOperationArgument()` multiple times to inject more than one argument; they are emitted
  in registration order, before the GraphQL variables.

<!-- source: tests/OperationArgument/CreateThing.graphql -->
```graphql
mutation CreateThing($name: String!) @requiresActor {
    createThing(name: $name) {
        id
        name
    }
}
```

**Generated code** — `Actor $actor` leads the signature and is forwarded to `graphql()`, while the
`@requiresActor` directive is gone from `OPERATION_DEFINITION`:

<!-- source: tests/OperationArgument/Generated/Mutation/CreateThing/CreateThingMutation.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Mutation\CreateThing;

use Ruudk\GraphQLCodeGenerator\OperationArgument\Actor;
use Ruudk\GraphQLCodeGenerator\OperationArgument\ActorTestClient;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class CreateThingMutation {
    public const string OPERATION_NAME = 'CreateThing';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        mutation CreateThing($name: String!) {
          createThing(name: $name) {
            id
            name
          }
        }
        
        GRAPHQL;

    public function __construct(
        private ActorTestClient $client,
    ) {}

    /**
     * @api
     */
    public function execute(
        Actor $actor,
        Stringable|string $name,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'name' => (string) $name,
            ],
            self::OPERATION_NAME,
            actor: $actor,
        );

        // @mago-expect analysis:mixed-argument(2)
        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }

    /**
     * @api
     * @throws CreateThingMutationFailedException
     */
    public function executeOrThrow(
        Actor $actor,
        Stringable|string $name,
    ) : Data {
        $data = $this->execute(
            $actor,
            $name,
        );

        if ($data->errors !== []) {
            throw new CreateThingMutationFailedException($data);
        }

        return $data;
    }
}
```

Your client's `graphql()` method receives the extra argument(s) positionally, so add them to its
signature (make them optional if only some operations forward them):

```php
public function graphql(
    string $query,
    array $variables = [],
    ?string $operationName = null,
    ?Actor $actor = null,
) : array {
    // forward $actor to the transport — e.g. as a header
}
```

### 🪪 Input Types for Custom Scalars

`withScalar()` maps a scalar to one PHP type, for what you send and for what you get back. Sometimes those
differ: you pass an id as a value object, but the server answers it as a string. Give the scalar an
`inputType` for the variables and the fields of input objects:

```php
SchemaConfig::create(/* ... */)
    ->withScalar('ID', Type::string(), inputType: Type::object(UserId::class));
```

The generated code then takes a `UserId` for every `ID` it sends, and answers every `ID` it gets back as a `string`:

```php
public function execute(UserId $id, UserFilter $filter) : Data
{
    $data = $this->client->graphql(
        self::OPERATION_DEFINITION,
        [
            'id' => $id,
            'filter' => $filter,
        ],
        self::OPERATION_NAME,
    );
```

A value of an input type goes to your client as it is, also inside a list or an input object, so the client
writes it as JSON. That is the place for an id that only a service can encode:

```php
public function graphql(string $query, array $variables = [], ?string $operationName = null) : array
{
    $variables = array_map($this->serialize(...), $variables);

    // send the request
}

private function serialize(mixed $value) : mixed
{
    return match (true) {
        $value instanceof UserId => $this->userIdEncoder->encode($value),
        $value instanceof JsonSerializable => $this->serialize($value->jsonSerialize()),
        is_array($value) => array_map($this->serialize(...), $value),
        default => $value,
    };
}
```

A scalar without an input type works as before: when its PHP type is `string`, the generated code also takes a
`Stringable` and casts it with `(string)`. A scalar with an input type is never cast, so a value object whose
`__toString()` is not its JSON value cannot end up in the request by accident.

### 🔓 Decode Custom Scalars with a Service

A `TypeInitializer` turns a scalar into an object with static code, like `new DateTimeImmutable($value)`.
Some values need a service: an id that is encrypted, or that names the class it belongs to. Give the
scalar a decoder, and every value of it that the server answers is decoded, without a word about it
in your queries:

```php
SchemaConfig::create(/* ... */)
    ->withScalar('ID', Type::string(), Type::object(NodeId::class))
    ->withScalarDecoder('ID', NodeIdDecoder::class, 'decode')
    ->withScalarFieldType('ID', static fn(string $type, string $field): ?Type => match ([$type, $field]) {
        ['User', 'id'] => Type::object(UserId::class),
        ['Project', 'id'] => Type::object(ProjectId::class),
        default => null,
    });
```

`withScalarFieldType()` is optional. It gives a field a narrower type than the scalar's, from the
name of the GraphQL type the field is selected on and the name of the field. The decoder then gets
that class as its second argument:

<!-- source: tests/ScalarDecoder/Generated/Query/Test/Data/Viewer.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data\Viewer\Manager;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public ?NodeId $bestFriendId {
        get => $this->bestFriendId ??= $this->data['bestFriendId'] !== null ? $this->decoders['ID']->decode($this->data['bestFriendId']) : null;
    }

    /**
     * @var list<NodeId>
     */
    public array $friendIds {
        get => $this->friendIds ??= array_map(fn($item) => $this->decoders['ID']->decode($item), $this->data['friendIds']);
    }

    public UserId $id {
        get => $this->id ??= $this->decoders['ID']->decode($this->data['id'], UserId::class);
    }

    public ?Manager $manager {
        get => $this->manager ??= $this->data['manager'] !== null ? new Manager($this->data['manager'], $this->decoders) : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'bestFriendId': null|string,
     *     'friendIds': list<string>,
     *     'id': string,
     *     'manager': null|array{
     *         'id': string,
     *         ...,
     *     },
     *     'name': string,
     *     ...,
     * } $data
     * @param array{
     *     'ID': NodeIdDecoder,
     *     ...,
     * } $decoders
     */
    public function __construct(
        private readonly array $data,
        private readonly array $decoders,
    ) {}
}
```

`User.id` is a `UserId`, while `friendIds` and `bestFriendId` have no narrower type and are a `NodeId`,
whose class the decoder reads from the id. Decoding is lazy: an id you never read is never decoded.
With `@narrow`, a field selected on the interface takes the type the field is narrowed to: in
`node(id: $id) { id ... on Project @narrow { name } }`, `id` is a `ProjectId`.

The decoder is any service with that method, so the generated code still has no runtime dependencies:

<!-- source: tests/ScalarDecoder/Fixture/NodeIdDecoder.php -->
```php
<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture;

use InvalidArgumentException;

/**
 * Turns an id like base64("User:1") into a UserId, and checks that it is of the class the field asks for.
 */
final readonly class NodeIdDecoder
{
    /**
     * @template T of NodeId
     * @param null|class-string<T> $class
     * @throws InvalidArgumentException
     * @return ($class is null ? NodeId : T)
     */
    public function decode(string $value, ?string $class = null): NodeId
    {
        $decoded = base64_decode($value, true);
        [$type, $id] = explode(':', $decoded === false ? '' : $decoded, 2) + [1 => '0'];

        $nodeId = match ($type) {
            'User' => new UserId((int) $id),
            'Project' => new ProjectId((int) $id),
            default => throw new InvalidArgumentException(sprintf('"%s" is no id of a known type.', $value)),
        };

        if ($class !== null && !$nodeId instanceof $class) {
            throw new InvalidArgumentException(sprintf('"%s" is no id of a %s.', $value, $class));
        }

        return $nodeId;
    }
}
```

The generated code receives the decoder in its `$decoders` map, under the name of the scalar, and
passes it on to every class that decodes. Pass it when you create the operation:

```php
$result = new TestQuery($client, decoders: [
    'ID' => new NodeIdDecoder(),
])->execute(new UserId(1));
```

With `enableSymfonyAutowireDecoders()`, the container wires it, apart from the hooks, which
`enableSymfonyAutowireHooks()` wires. Pass `service:` when the id of the service is not its class:

```php
->withScalarDecoder('ID', GlobalIdFactory::class, 'fromGlobalId', service: 'app.global_id_factory')
```

The keys of `@indexBy` and `@groupBy` stay the JSON value, because an object cannot be an array key.
What you send is not decoded: see the input types above for that side.

## Requirements

- **PHP 8.5+** (uses property hooks, readonly classes, and other modern features)
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
     *     ...,
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
    public ProjectView $projectView {
        get => $this->projectView ??= new ProjectView($this->data);
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'name': string,
     *     'state': null|string,
     *     ...,
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
        dump(
            asPullRequest: $node->pullRequestInfo->title . ' is merged: '
                . var_export($node->pullRequestInfo->merged, true),
        );
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
- **Mago** for formatting, linting and static analysis of PHP and Twig
- **PHPUnit** for testing

Run the quality checks:

```bash
vendor/bin/mago format
vendor/bin/mago lint
vendor/bin/mago analyze
vendor/bin/mago twig:fmt
vendor/bin/mago twig:lint
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

