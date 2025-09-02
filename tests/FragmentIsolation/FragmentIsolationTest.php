<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\GetWorkflowForAdminQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class FragmentIsolationTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testDirectSelectionTakesPrecedence() : void
    {
        $result = new GetWorkflowForAdminQuery($this->getClient([
            'data' => [
                'workflow' => [
                    'id' => 'flow-123',
                    'transaction' => [
                        'transfers' => [
                            [
                                'id' => 'transfer-1',
                                'customer' => [
                                    'id' => 'customer-1',
                                ],
                                'canBeCollected' => true,
                                'transferReversals' => [
                                    [
                                        'id' => 'reversal-1',
                                        'transfer' => [
                                            'metadata' => 'test-meta',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], new GraphQLRequestMatcher(
            variables: [
                'workflowId' => 'flow-123',
            ],
            operationName: 'GetWorkflowForAdmin',
        )))->execute('flow-123');
        self::assertNotNull($result->workflow);
        $workflow = $result->workflow;
        self::assertObjectNotHasProperty('id', $workflow, 'Workflow should NOT have direct id property');
        self::assertObjectHasProperty('transaction', $workflow, 'Workflow should have transaction property');
        self::assertObjectHasProperty('adminViewSystemShowWorkflow', $workflow, 'Workflow should have fragment property');
        $directTransaction = $workflow->transaction;
        self::assertNotNull($directTransaction);
        $directTransfer = $directTransaction->transfers[0];
        self::assertObjectHasProperty('id', $directTransfer, 'Direct Transfer should have id property');
        self::assertObjectHasProperty('customer', $directTransfer, 'Direct Transfer should have customer property');
        self::assertObjectNotHasProperty('canBeCollected', $directTransfer, 'Direct Transfer should NOT have canBeCollected property');
        self::assertObjectNotHasProperty('transferReversals', $directTransfer, 'Direct Transfer should NOT have transferReversals property');
        self::assertSame('transfer-1', $directTransfer->id);
        self::assertSame('customer-1', $directTransfer->customer->id);
        $fragment = $workflow->adminViewSystemShowWorkflow;
        self::assertSame('flow-123', $fragment->id);
        $fragmentTransaction = $fragment->transaction;
        self::assertNotNull($fragmentTransaction);
        $fragmentTransfer = $fragmentTransaction->transfers[0];
        self::assertSame('transfer-1', $fragmentTransfer->id);
        self::assertTrue($fragmentTransfer->adminViewSystemTransferRow->canBeCollected);
        $transferState = $fragmentTransfer->adminViewSystemTransferRow->adminViewSystemTransferState;
        self::assertCount(1, $transferState->transferReversals);
        self::assertSame('reversal-1', $transferState->transferReversals[0]->id);
    }
}
