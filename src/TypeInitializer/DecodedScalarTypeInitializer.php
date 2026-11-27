<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Type\DecodedScalarType;
use Symfony\Component\TypeInfo\Type;

/**
 * Decodes a scalar with the decoder the generated class receives in `$decoders` under the name of the
 * scalar. A field with a narrower type passes its class, so the decoder can make that class.
 *
 * @implements TypeInitializer<DecodedScalarType>
 */
final readonly class DecodedScalarTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type): bool
    {
        return $type instanceof DecodedScalarType;
    }

    #[Override]
    public function initialize(
        Type $type,
        CodeGenerator $generator,
        string $variable,
        DelegatingTypeInitializer $delegator,
    ): string {
        $arguments = $variable;

        if ($type->narrowed) {
            $arguments .= sprintf(', %s::class', $generator->import($type->getClassName()));
        }

        return sprintf(
            '$this->decoders[%s]->%s(%s)',
            var_export($type->decoder->scalar, true),
            $type->decoder->method,
            $arguments,
        );
    }
}
