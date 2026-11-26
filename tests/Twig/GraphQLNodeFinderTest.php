<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;

final class GraphQLNodeFinderTest extends TestCase
{
    public function testFindsBlocksWithAndWithoutSchema(): void
    {
        $blocks = $this->createFinder()->find(<<<'TWIG'
            {% graphql %}
            fragment A on User { id }
            {% endgraphql %}

            {%- graphql schema 'billing' -%}
            fragment B on Invoice { id }
            {%- endgraphql -%}
            TWIG);

        self::assertEquals(
            [
                new GraphQLBlock(null, "fragment A on User { id }\n", 1),
                new GraphQLBlock('billing', 'fragment B on Invoice { id }', 5),
            ],
            $blocks,
        );
    }

    public function testSchemaHasToBeAString(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessageIsOrContains(
            'The schema of a graphql block has to be a string, like {% graphql schema \'billing\' %}',
        );

        $this->createFinder()->find(
            "{% graphql schema billing %}\nfragment A on User { id }\n{% endgraphql %}",
            'template.html.twig',
        );
    }

    public function testRendersBlocksWithAndWithoutSchemaAsNothing(): void
    {
        $twig = new Environment(new ArrayLoader([
            'template.html.twig' => "{% graphql %}fragment A on User { id }{% endgraphql %}{% graphql schema 'billing' %}fragment B on Invoice { id }{% endgraphql %}<p>Hello</p>",
        ]));
        $twig->addExtension(new GraphQLExtension());

        self::assertSame('<p>Hello</p>', $twig->render('template.html.twig'));
    }

    private function createFinder(): GraphQLNodeFinder
    {
        $twig = new Environment(new ArrayLoader([]));
        $twig->addExtension(new GraphQLExtension());

        return new GraphQLNodeFinder($twig);
    }
}
