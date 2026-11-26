<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchemaSelectorTest extends TestCase
{
    public function testSelectsABlockWithoutSchemaWhenOnlyOneSchemaReadsTheFile(): void
    {
        self::assertTrue(new SchemaSelector()->selects(null, 'App\Controller::__construct::$query'));
        self::assertTrue(new SchemaSelector('billing', ['billing'])->selects(
            null,
            'App\Controller::__construct::$query',
        ));
    }

    public function testSelectsABlockThatNamesTheSchema(): void
    {
        $selector = new SchemaSelector('billing', ['billing', 'shipping']);

        self::assertTrue($selector->selects('billing', 'App\Controller::__construct::$query'));
        self::assertFalse($selector->selects('shipping', 'App\Controller::__construct::$query'));
    }

    public function testBlockHasToNameItsSchemaWhenMoreSchemasReadTheFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'App\Controller::__construct::$query has to name its schema, because the schemas "billing", "shipping" read it.',
        );

        new SchemaSelector('billing', ['billing', 'shipping'])->selects(null, 'App\Controller::__construct::$query');
    }

    public function testBlockCannotNameASchemaThatDoesNotReadTheFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'App\Controller::__construct::$query names the schema "biling", but only the schemas "billing", "shipping" read it.',
        );

        new SchemaSelector('billing', ['billing', 'shipping'])->selects(
            'biling',
            'App\Controller::__construct::$query',
        );
    }

    public function testBlockCannotNameASchemaWhenOnlyOneNamedSchemaReadsTheFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'App\Controller::__construct::$query names the schema "shipping", but only the schema "billing" reads it.',
        );

        new SchemaSelector('billing', ['billing'])->selects('shipping', 'App\Controller::__construct::$query');
    }

    public function testBlockCannotNameASchemaWhenTheSchemaThatReadsTheFileHasNoName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(
            'App\Controller::__construct::$query names the schema "billing", but the schema that reads it has no name.',
        );

        new SchemaSelector()->selects('billing', 'App\Controller::__construct::$query');
    }
}
