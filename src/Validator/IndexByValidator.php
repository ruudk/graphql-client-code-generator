<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Validator;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\ValidationRule;
use InvalidArgumentException;
use Override;
use Ruudk\GraphQLCodeGenerator\RecursiveTypeFinder;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Webmozart\Assert\Assert;

final class IndexByValidator extends ValidationRule
{
    /**
     * @param array<string, SymfonyType|array{SymfonyType, SymfonyType}> $scalars
     */
    public function __construct(
        private array $scalars,
    ) {}

    #[Override]
    public function getVisitor(QueryValidationContext $context) : array
    {
        return [
            NodeKind::FIELD => function (Node $node) use ($context) : void {
                Assert::isInstanceOf($node, FieldNode::class);

                if ($node->directives->count() === 0) {
                    return;
                }

                $indexBy = null;
                foreach ($node->directives as $directive) {
                    if ($directive->name->value !== 'indexBy') {
                        continue;
                    }

                    if ($directive->arguments->count() === 0) {
                        continue;
                    }

                    if ($directive->arguments[0]->name->value !== 'field') {
                        continue;
                    }

                    if ( ! $directive->arguments[0]->value instanceof StringValueNode) {
                        continue;
                    }

                    $indexBy = $directive->arguments[0]->value->value;

                    break;
                }

                if ($indexBy === null) {
                    return;
                }

                $indexBy = explode('.', $indexBy);

                $type = $context->getType();

                if ($type instanceof NonNull) {
                    $type = $type->getWrappedType();
                }

                Assert::isInstanceOf($type, ListOfType::class, '@indexBy can only be used on lists');

                $namedType = Type::getNamedType($type);

                $listOfType = $context->getSchema()->getType($namedType->name());
                Assert::notNull($listOfType);

                $indexByType = RecursiveTypeFinder::find($listOfType, $indexBy);

                $indexByType = Type::getNamedType($indexByType);

                $possibleArrayKeyTypes = [];
                foreach ($this->scalars as $name => $scalarType) {
                    if (is_array($scalarType)) {
                        [$scalarType] = $scalarType;
                    }

                    if ( ! $scalarType instanceof SymfonyType\BuiltinType) {
                        continue;
                    }

                    if ( ! in_array($scalarType->getTypeIdentifier(), [TypeIdentifier::STRING, TypeIdentifier::INT], true)) {
                        continue;
                    }

                    $possibleArrayKeyTypes[] = $name;
                }

                Assert::inArray(
                    $indexByType->name(),
                    $possibleArrayKeyTypes,
                    sprintf('@indexBy(field: "%s") cannot be used because the field is not a valid array key type.', $indexByType->name()),
                );

                $found = $this->find($node, $indexBy);

                if ($found !== null) {
                    return;
                }

                throw new InvalidArgumentException(sprintf('Field "%s" is not selected in the indexBy directive', implode('.', $indexBy)));
            },
        ];
    }

    /**
     * @param non-empty-list<string> $indexBy
     */
    public function find(FieldNode $node, array $indexBy) : ?FieldNode
    {
        $field = array_shift($indexBy);

        foreach ($node->selectionSet->selections ?? [] as $selection) {
            if ( ! $selection instanceof FieldNode) {
                continue;
            }

            if ($selection->name->value !== $field) {
                continue;
            }

            if ($indexBy === []) {
                return $selection;
            }

            return $this->find($selection, $indexBy);
        }

        return null;
    }
}
