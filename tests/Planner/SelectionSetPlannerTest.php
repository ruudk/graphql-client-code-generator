<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Tests\Planner;

use GraphQL\Language\Parser;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\DirectiveProcessor;
use Ruudk\GraphQLCodeGenerator\GraphQL\DocumentNodeWithSource;
use Ruudk\GraphQLCodeGenerator\GraphQL\FragmentDefinitionNodeWithSource;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\SelectionSetPlanner;
use Ruudk\GraphQLCodeGenerator\TypeMapper;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final class SelectionSetPlannerTest extends TestCase
{
    public function testFragmentSpreadFieldsMergeInNestedClasses() : void
    {
        $schema = BuildSchema::build('
            type Query { item: Item }
            type Item { id: ID! details: [Detail!]! }
            type Detail { id: ID! metadata: [Metadata!]! }
            type Metadata { key: String! value: String! }
        ');
        $document = Parser::parse('
            fragment ItemWithDetails on Item {
                id
                details {
                    id
                    ...DetailBasic
                    metadata { value }
                }
            }
            fragment DetailBasic on Detail {
                metadata { key }
            }
        ');
        $document = DocumentNodeWithSource::create($document, '');
        // Collect fragments
        $fragments = [];
        $fragmentTypes = [];
        foreach ($document->definitions as $def) {
            if ($def instanceof FragmentDefinitionNodeWithSource) {
                $fragments[$def->name->value] = $def;
                $type = $schema->getType($def->typeCondition->name->value);
                self::assertNotNull($type, 'Fragment type ' . $def->typeCondition->name->value . ' should exist');
                $fragmentTypes[$def->name->value] = $type;
            }
        }

        // Create minimal config using reflection to bypass constructor
        $configClass = new ReflectionClass(Config::class);
        $configInstance = $configClass->newInstanceWithoutConstructor();
        // Set required properties using reflection
        $namespaceProperty = $configClass->getProperty('namespace');
        $namespaceProperty->setValue($configInstance, 'Test\\Generated');
        $outputDirProperty = $configClass->getProperty('outputDir');
        $outputDirProperty->setValue($configInstance, '/tmp/generated');
        // Set optional properties that have defaults
        $useNodeNameProperty = $configClass->getProperty('useNodeNameForEdgeNodes');
        $useNodeNameProperty->setValue($configInstance, false);
        $useConnectionNameProperty = $configClass->getProperty('useConnectionNameForConnections');
        $useConnectionNameProperty->setValue($configInstance, false);
        $useEdgeNameProperty = $configClass->getProperty('useEdgeNameForEdges');
        $useEdgeNameProperty->setValue($configInstance, false);
        $indexByDirectiveProperty = $configClass->getProperty('indexByDirective');
        $indexByDirectiveProperty->setValue($configInstance, false);
        $addNodesProperty = $configClass->getProperty('addNodesOnConnections');
        $addNodesProperty->setValue($configInstance, false);
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
        $planner = new SelectionSetPlanner(
            $configInstance,
            $schema,
            $typeMapper,
            new DirectiveProcessor(),
            new EnglishInflector(),
        );
        // Set the fragments on the planner
        foreach ($fragments as $name => $def) {
            $planner->setFragmentDefinition($name, $def, []);
            $planner->setFragmentType($name, $fragmentTypes[$name]);
        }

        // Plan the ItemWithDetails fragment
        $itemFragment = $fragments['ItemWithDetails'];
        $itemType = $fragmentTypes['ItemWithDetails'];
        $result = $planner->plan(
            '',
            $itemFragment->selectionSet,
            $itemType,
            '/tmp/generated/Fragment/ItemWithDetails',
            'Test\\Generated\\Fragment\\ItemWithDetails',
            'fragment',
            isGeneratingTopLevelFragment: true,
        );
        // Find the Detail class plan in the result
        $detailClassPlan = null;
        foreach ($result->plannerResult->classes as $class) {
            if ($class instanceof DataClassPlan && str_contains($class->path, 'ItemWithDetails/Detail.php')) {
                $detailClassPlan = $class;

                break;
            }
        }

        self::assertNotNull($detailClassPlan, 'Detail class should be generated');
        $payloadShapeString = (string) $detailClassPlan->payloadShape;
        // The payload shape should include BOTH 'key' and 'value' in metadata
        self::assertStringContainsString(
            "'key'",
            $payloadShapeString,
            'Payload shape should include key from DetailBasic fragment. Got: ' . $payloadShapeString,
        );
        self::assertStringContainsString(
            "'value'",
            $payloadShapeString,
            'Payload shape should include value from direct selection. Got: ' . $payloadShapeString,
        );
    }

    public function testNestedFragmentPayloadShapeMerging() : void
    {
        $schema = BuildSchema::build('
            type Query { payment: Payment }
            type Payment { id: ID! payouts: [Payout!]! }
            type Payout { id: ID! reversals: [Reversal!]! }
            type Reversal { id: ID! reason: String! }
        ');
        $document = Parser::parse('
            fragment PaymentDetails on Payment {
                id
                payouts {
                    id
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
        $document = DocumentNodeWithSource::create($document, '');
        // Collect fragments
        $fragments = [];
        $fragmentTypes = [];
        foreach ($document->definitions as $def) {
            if ($def instanceof FragmentDefinitionNodeWithSource) {
                $fragments[$def->name->value] = $def;
                $type = $schema->getType($def->typeCondition->name->value);
                self::assertNotNull($type, 'Fragment type ' . $def->typeCondition->name->value . ' should exist');
                $fragmentTypes[$def->name->value] = $type;
            }
        }

        // Create minimal config using reflection to bypass constructor
        $configClass = new ReflectionClass(Config::class);
        $configInstance = $configClass->newInstanceWithoutConstructor();
        // Set required properties using reflection
        $namespaceProperty = $configClass->getProperty('namespace');
        $namespaceProperty->setValue($configInstance, 'Test\\Generated');
        $outputDirProperty = $configClass->getProperty('outputDir');
        $outputDirProperty->setValue($configInstance, '/tmp/generated');
        // Set optional properties that have defaults
        $useNodeNameProperty = $configClass->getProperty('useNodeNameForEdgeNodes');
        $useNodeNameProperty->setValue($configInstance, false);
        $useConnectionNameProperty = $configClass->getProperty('useConnectionNameForConnections');
        $useConnectionNameProperty->setValue($configInstance, false);
        $useEdgeNameProperty = $configClass->getProperty('useEdgeNameForEdges');
        $useEdgeNameProperty->setValue($configInstance, false);
        $indexByDirectiveProperty = $configClass->getProperty('indexByDirective');
        $indexByDirectiveProperty->setValue($configInstance, false);
        $addNodesProperty = $configClass->getProperty('addNodesOnConnections');
        $addNodesProperty->setValue($configInstance, false);
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
        $planner = new SelectionSetPlanner(
            $configInstance,
            $schema,
            $typeMapper,
            new DirectiveProcessor(),
            new EnglishInflector(),
        );
        // Set the fragments on the planner
        foreach ($fragments as $name => $def) {
            $planner->setFragmentDefinition($name, $def, []);
            $planner->setFragmentType($name, $fragmentTypes[$name]);
        }

        // Plan the PaymentDetails fragment
        $paymentFragment = $fragments['PaymentDetails'];
        $paymentType = $fragmentTypes['PaymentDetails'];
        $result = $planner->plan(
            '',
            $paymentFragment->selectionSet,
            $paymentType,
            '/tmp/generated/Fragment/PaymentDetails',
            'Test\\Generated\\Fragment\\PaymentDetails',
            'fragment',
            isGeneratingTopLevelFragment: true,
        );
        // Find the Payout class plan in the result
        $payoutClassPlan = null;
        foreach ($result->plannerResult->classes as $class) {
            if ($class instanceof DataClassPlan && str_contains($class->path, 'PaymentDetails/Payout.php')) {
                $payoutClassPlan = $class;

                break;
            }
        }

        self::assertNotNull($payoutClassPlan, 'Payout class should be generated');
        $payloadShapeString = (string) $payoutClassPlan->payloadShape;
        // The reversals array should include BOTH 'id' and 'reason'
        self::assertStringContainsString(
            "'id'",
            $payloadShapeString,
            'Payload shape should include id from PayoutRow fragment. Got: ' . $payloadShapeString,
        );
        self::assertStringContainsString(
            "'reason'",
            $payloadShapeString,
            'Payload shape should include reason from direct selection. Got: ' . $payloadShapeString,
        );
    }
}
