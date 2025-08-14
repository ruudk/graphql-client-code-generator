<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Override;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements TypeInitializer<Type\CollectionType<*>>
 */
final readonly class CollectionTypeInitializer implements TypeInitializer
{
    #[Override]
    public function getType() : string
    {
        return Type\CollectionType::class;
    }

    #[Override]
    public function __invoke(Type $type, callable $importer, string $variable, DelegatingTypeInitializer $delegator) : string
    {
        return sprintf(
            'array_map(fn($item) => %s, %s)',
            $delegator($type->getCollectionValueType(), $importer, '$item'),
            $variable,
        );
    }
}
