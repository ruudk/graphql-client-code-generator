<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Validator;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

final readonly class IndexByValidator
{
    public function __construct(
        private Schema $schema,
    ) {}

    public function __invoke(Node $node) : void
    {
        $typeInfo = new TypeInfo($this->schema);

        Visitor::visit($node, Visitor::visitWithTypeInfo($typeInfo, [
            NodeKind::FIELD => function (Node $node) use ($typeInfo) : void {
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

                $type = $typeInfo->getType();

                if ($type instanceof NonNull) {
                    $type = $type->getWrappedType();
                }

                Assert::isInstanceOf($type, ListOfType::class, '@indexBy can only be used on lists');

                $namedType = Type::getNamedType($type);
                Assert::notNull($namedType);

                $listOfType = $this->schema->getType($namedType->name());

                Assert::isInstanceOf($listOfType, HasFieldsType::class);
                $indexByType = $listOfType->getField($indexBy)->getType();

                if ( ! $indexByType instanceof NonNull) {
                    throw new InvalidArgumentException(sprintf('Field "%s" in the indexBy directive must be non-null', $indexBy));
                }

                $indexByType = $indexByType->getWrappedType();

                Assert::isInstanceOfAny($indexByType, [
                    IDType::class,
                    StringType::class,
                    IntType::class,
                ], '@indexBy can only be used on fields of type ID, String or Int');

                Assert::notNull($node->selectionSet);
                foreach ($node->selectionSet->selections as $selection) {
                    if ( ! $selection instanceof FieldNode) {
                        continue;
                    }

                    if ($selection->name->value === $indexBy) {
                        return;
                    }
                }

                throw new InvalidArgumentException(sprintf('Field "%s" is not selected in the indexBy directive', $indexBy));
            },
        ]));
    }
}
