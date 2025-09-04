<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Query\Projectsd4cba6\ProjectsQuery;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableGeneratedAttribute()
            ->enableDumpOrThrows()
            ->enableDumpEnumIsMethods()
            ->withInlineProcessingDirectory(__DIR__)
            ->withTwigProcessingDirectory(__DIR__ . '/templates');
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $query = new ProjectsQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
                'projects' => [
                    [
                        'id' => '111',
                        'name' => 'GraphQL Code Generator',
                        'description' => 'The best GraphQL client generator',
                        'state' => 'ACTIVE',
                    ],
                    [
                        'id' => '222',
                        'name' => 'Some other project',
                        'description' => null,
                        'state' => 'ARCHIVED',
                    ],
                ],
            ],
        ]));

        $twig = new Environment(
            new FilesystemLoader([
                __DIR__ . '/templates',
            ]),
            [
                'debug' => true,
                'strict_variables' => true,
            ],
        );
        $twig->addExtension(new GraphQLExtension());

        $output = new SomeController($twig, $query)->__invoke();
        $output = trim(preg_replace('/\n\s*\n/', "\n", $output) ?? '');

        self::assertSame(<<<'HTML'
            <h1>Good day, Ruud Kamphuis</h1>
            <h3>Your projects</h3>
            <li>
                #111 - GraphQL Code Generator<br>
                The best GraphQL client generator
                <hr>
                <a href="#archive">Archive</a>
            </li>
            <li>
                #222 - Some other project<br>
                <hr>
                <a href="#unarchive">Unarchive</a>
            </li>
            HTML, $output);
    }
}
