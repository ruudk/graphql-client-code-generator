<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\VariableDefinitionNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\TypeInfo;

final class UsedTypesCollector
{
    private Schema $schema;
    private TypeInfo $typeInfo;

    /**
     * @var array<string, FragmentDefinitionNode>
     */
    private array $fragments = [];

    /**
     * @var list<string>
     */
    public private(set) array $usedTypes = [];

    /**
     * @var array<string, true>
     */
    private array $visitedFragments = [];

    public function __construct(
        Schema $schema,
    ) {
        $this->schema = $schema;
        $this->typeInfo = new TypeInfo($schema);
    }

    public function analyze(DocumentNode $doc) : void
    {
        $this->indexFragments($doc);

        foreach ($doc->definitions as $def) {
            $this->visitNode($def);
        }
    }

    private function indexFragments(DocumentNode $doc) : void
    {
        $this->fragments = [];
        foreach ($doc->definitions as $def) {
            if ( ! $def instanceof FragmentDefinitionNode) {
                continue;
            }

            $this->fragments[$def->name->value] = $def;
        }
    }

    private function visitNode(Node $node) : void
    {
        $wrapped = Visitor::visitWithTypeInfo($this->typeInfo, [
            'enter' => function (Node $n) {
                // Field selections: check if the field returns an enum or input object type
                if ($n instanceof FieldNode) {
                    $fieldType = $this->typeInfo->getType();
                    if ($fieldType !== null) {
                        $named = Type::getNamedType($fieldType);
                        
                        if (($named instanceof EnumType || $named instanceof InputObjectType) && ! in_array($named->name, $this->usedTypes, true)) {
                            $this->usedTypes[] = $named->name;
                        }
                    }
                    
                    return null;
                }
                
                // Variable definitions: record declared input/enum type (handles lists/non-nulls)
                if ($n instanceof VariableDefinitionNode) {
                    $decl = AST::typeFromAST($this->schema->getType(...), $n->type);

                    if ($decl !== null) {
                        $named = Type::getNamedType($decl);

                        if (($named instanceof EnumType || $named instanceof InputObjectType) && ! in_array($named->name, $this->usedTypes, true)) {
                            $this->usedTypes[] = $named->name;
                        }
                    }

                    return null;
                }

                // Enum literal position -> expected input type is an Enum
                if ($n instanceof EnumValueNode) {
                    $named = Type::getNamedType($this->typeInfo->getInputType());

                    if ($named instanceof EnumType && ! in_array($named->name, $this->usedTypes, true)) {
                        $this->usedTypes[] = $named->name;
                    }

                    return null;
                }

                // Object literal -> expected input type is an InputObject
                if ($n instanceof ObjectValueNode) {
                    $named = Type::getNamedType($this->typeInfo->getInputType());

                    if ($named instanceof InputObjectType && ! in_array($named->name, $this->usedTypes, true)) {
                        $this->usedTypes[] = $named->name;
                    }

                    return null;
                }

                // Variable used at a value position -> record expected type (covers both enums & inputs)
                if ($n instanceof VariableNode) {
                    $t = $this->typeInfo->getInputType();
                    $named = Type::getNamedType($t);

                    if (($named instanceof EnumType || $named instanceof InputObjectType) && ! in_array($named->name, $this->usedTypes, true)) {
                        $this->usedTypes[] = $named->name;
                    }

                    return null;
                }

                // Follow fragment spreads (avoid cycles)
                if ($n instanceof FragmentSpreadNode) {
                    $name = $n->name->value;

                    if (isset($this->visitedFragments[$name])) {
                        return null;
                    }

                    $frag = $this->fragments[$name] ?? null;

                    if ( ! $frag) {
                        return null;
                    }

                    $this->visitedFragments[$name] = true;
                    // Recurse under same TypeInfo context
                    $this->visitNode($frag);

                    return null;
                }

                return null;
            },
        ]);

        Visitor::visit($node, $wrapped);
    }
}
