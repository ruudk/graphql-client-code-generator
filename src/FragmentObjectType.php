<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Symfony\Component\TypeInfo\Type\ObjectType;

final class FragmentObjectType extends ObjectType
{
    public function __construct(
        string $className,
        public readonly string $fragmentName,
    ) {
        parent::__construct($className);
    }
}
