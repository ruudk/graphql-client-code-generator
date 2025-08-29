<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use GraphQL\Language\Parser;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\TypeMapper;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class FragmentPayloadShapeTest extends TestCase
{
    public function testFragmentSpreadFieldsShouldMergeInNestedPayloadShapes() : void
    {
        $schema = BuildSchema::build('
            type Query { item: Item }
            type Item { details: [Detail!]! }
            type Detail { metadata: [Metadata!]! }
            type Metadata { key: String! value: String! }
        ');
        $document = Parser::parse('
            fragment ItemWithDetails on Item {
                details {
                    ...DetailBasic
                    metadata { value }
                }
            }
            fragment DetailBasic on Detail {
                metadata { key }
            }
        ');
        $fragments = [];
        $fragmentTypes = [];
        foreach ($document->definitions as $def) {
            if ($def instanceof \GraphQL\Language\AST\FragmentDefinitionNode) {
                $fragments[$def->name->value] = [$def, []];
                $type = $schema->getType($def->typeCondition->name->value);
                self::assertNotNull($type, 'Fragment type ' . $def->typeCondition->name->value . ' should exist');
                $fragmentTypes[$def->name->value] = $type;
            }
        }

        $typeMapper = new TypeMapper(
            $schema,
            [
                'ID' => [SymfonyType::string(), SymfonyType::string()],
                'String' => [SymfonyType::string(), SymfonyType::string()],
            ],
            [],
            [],
            [],
        );
        $builder = new PayloadShapeBuilder($schema, $typeMapper, $fragments, $fragmentTypes);
        // Get the details field from ItemWithDetails fragment
        $itemFragment = $fragments['ItemWithDetails'][0];
        $detailsField = null;
        foreach ($itemFragment->selectionSet->selections as $sel) {
            if ($sel instanceof \GraphQL\Language\AST\FieldNode && $sel->name->value === 'details') {
                $detailsField = $sel;

                break;
            }
        }

        self::assertNotNull($detailsField);
        self::assertNotNull($detailsField->selectionSet, 'Details field should have a selection set');
        // Build payload shape for the details field selection
        $detailType = $schema->getType('Detail');
        self::assertNotNull($detailType, 'Detail type should exist');
        $shape = $builder->buildPayloadShape($detailsField->selectionSet, $detailType);
        $shapeString = (string) $shape->toArrayShape();
        // Should have BOTH key (from DetailBasic fragment) and value (from direct selection)
        self::assertStringContainsString(
            "'key': string",
            $shapeString,
            'Should include key from DetailBasic fragment',
        );
        self::assertStringContainsString(
            "'value': string",
            $shapeString,
            'Should include value from direct selection',
        );
    }

    public function testNestedFragmentFieldsMergeCorrectly() : void
    {
        $schema = BuildSchema::build('
            type Query { payment: Payment }
            type Payment { payouts: [Payout!]! }
            type Payout { reversals: [Reversal!]! }
            type Reversal { id: ID! reason: String! }
        ');
        $document = Parser::parse('
            fragment PaymentDetails on Payment {
                payouts {
                    ...PayoutRow
                    reversals {
                        reason
                    }
                }
            }
            fragment PayoutRow on Payout {
                reversals {
                    id
                }
            }
        ');
        $fragments = [];
        $fragmentTypes = [];
        foreach ($document->definitions as $def) {
            if ($def instanceof \GraphQL\Language\AST\FragmentDefinitionNode) {
                $fragments[$def->name->value] = [$def, []];
                $type = $schema->getType($def->typeCondition->name->value);
                self::assertNotNull($type, 'Fragment type ' . $def->typeCondition->name->value . ' should exist');
                $fragmentTypes[$def->name->value] = $type;
            }
        }

        $typeMapper = new TypeMapper(
            $schema,
            [
                'ID' => [SymfonyType::string(), SymfonyType::string()],
                'String' => [SymfonyType::string(), SymfonyType::string()],
            ],
            [],
            [],
            [],
        );
        $builder = new PayloadShapeBuilder($schema, $typeMapper, $fragments, $fragmentTypes);
        // Get the payouts field from PaymentDetails fragment
        $paymentFragment = $fragments['PaymentDetails'][0];
        $payoutsField = null;
        foreach ($paymentFragment->selectionSet->selections as $sel) {
            if ($sel instanceof \GraphQL\Language\AST\FieldNode && $sel->name->value === 'payouts') {
                $payoutsField = $sel;

                break;
            }
        }

        self::assertNotNull($payoutsField);
        self::assertNotNull($payoutsField->selectionSet, 'Payouts field should have a selection set');
        // Build payload shape for the payouts field selection
        $payoutType = $schema->getType('Payout');
        self::assertNotNull($payoutType, 'Payout type should exist');
        $shape = $builder->buildPayloadShape($payoutsField->selectionSet, $payoutType);
        $shapeString = (string) $shape->toArrayShape();
        // Should have BOTH id (from PayoutRow fragment) and reason (from direct selection)
        self::assertStringContainsString(
            "'id': string",
            $shapeString,
            'Should include id from PayoutRow fragment',
        );
        self::assertStringContainsString(
            "'reason': string",
            $shapeString,
            'Should include reason from direct selection',
        );
    }
}
