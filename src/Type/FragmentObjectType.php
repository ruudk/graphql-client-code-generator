<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * @extends SymfonyType\ObjectType<object>
 */
final class FragmentObjectType extends SymfonyType\ObjectType
{
    public function __construct(
        string $className,
        public readonly string $fragmentName,
        public readonly NamedType & Type $fragmentType,
    ) {
        parent::__construct($className);
    }
}
