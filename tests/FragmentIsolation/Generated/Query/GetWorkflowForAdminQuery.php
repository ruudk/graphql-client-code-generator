<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class GetWorkflowForAdminQuery {
    public const string OPERATION_NAME = 'GetWorkflowForAdmin';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query GetWorkflowForAdmin($workflowId: WorkflowId!) {
          workflow(id: $workflowId) {
            transaction {
              transfers {
                id
                customer {
                  id
                }
              }
            }
            ...AdminViewSystemShowWorkflow
          }
        }
        
        fragment AdminViewSystemShowWorkflow on Workflow {
          id
          transaction {
            transfers {
              id
              ...AdminViewSystemTransferRow
              transferReversals {
                ...AdminViewSystemTransferReversalRow
              }
            }
          }
        }
        
        fragment AdminViewSystemTransferRow on Transfer {
          canBeCollected
          ...AdminViewSystemTransferState
        }
        
        fragment AdminViewSystemTransferState on Transfer {
          transferReversals {
            id
          }
        }
        
        fragment AdminViewSystemTransferReversalRow on TransferReversal {
          transfer {
            metadata
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    public function execute(
        int|string|float|bool $workflowId,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'workflowId' => $workflowId,
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
