<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksBatched\Access;
use Ruudk\GraphQLCodeGenerator\HooksBatched\ComputeAccessHook;
use Ruudk\GraphQLCodeGenerator\HooksBatched\FindOrgPlanHook;
use Ruudk\GraphQLCodeGenerator\HooksBatched\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\HookLoader;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Query\Test\Data\Organization;
use Ruudk\GraphQLCodeGenerator\HooksBatched\OrgPlan;
use Ruudk\GraphQLCodeGenerator\HooksBatched\User;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Organization>
     */
    public array $organizations {
        get => $this->organizations ??= array_map(fn($item) => new Organization($item, $this->loaders), $this->data['organizations']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @var array{
     *     findOrgPlan: HookLoader<array{string}, OrgPlan>,
     *     computeAccess: HookLoader<array{string, string}, Access>,
     *     findUserById: HookLoader<array{string}, null|User>,
     *     ...<string, HookLoader<array<int, mixed>, mixed>>,
     * }
     */
    private readonly array $loaders;

    /**
     * @param array{
     *     'organizations': list<array{
     *         'id': string,
     *         'name': string,
     *         'repositories': list<array{
     *             'id': string,
     *             'name': string,
     *             'ownerId': string,
     *             'reviewerId': string,
     *             ...<int|string, mixed>,
     *         }>,
     *         ...<int|string, mixed>,
     *     }>,
     *     ...<int|string, mixed>,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...<int|string, mixed>,
     * }> $errors
     * @param array{
     *     'computeAccess': ComputeAccessHook,
     *     'findOrgPlan': FindOrgPlanHook,
     *     'findUserById': FindUserByIdHook,
     *     ...<int|string, mixed>,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);

        $this->loaders = [
            'findOrgPlan' => new HookLoader(
                $this->collectHookFindOrgPlanInputs(...),
                $this->hooks['findOrgPlan']->__invoke(...),
            ),
            'computeAccess' => new HookLoader(
                $this->collectHookComputeAccessInputs(...),
                $this->hooks['computeAccess']->__invoke(...),
            ),
            'findUserById' => new HookLoader(
                $this->collectHookFindUserByIdInputs(...),
                $this->hooks['findUserById']->__invoke(...),
            ),
        ];
    }

    /**
     * @return iterable<array{object, array{string}}>
     */
    private function collectHookFindOrgPlanInputs() : iterable
    {
        foreach ($this->organizations as $item) {
            yield [$item, [$item->id]];
        }
    }

    /**
     * @return iterable<array{object, array{string, string}}>
     */
    private function collectHookComputeAccessInputs() : iterable
    {
        foreach ($this->organizations as $item) {
            foreach ($item->repositories as $item1) {
                yield [$item1, [$item1->ownerId, $item1->reviewerId]];
            }
        }
    }

    /**
     * @return iterable<array{object, array{string}}>
     */
    private function collectHookFindUserByIdInputs() : iterable
    {
        foreach ($this->organizations as $item) {
            foreach ($item->repositories as $item1) {
                yield [$item1, [$item1->ownerId]];
            }
        }
    }
}
