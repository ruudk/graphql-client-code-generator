<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Ruudk\CodeGenerator\CodeGenerator;

/**
 * Builds the expressions that read the keys of `@indexBy` or `@groupBy` from the payload of an item.
 */
final readonly class ListKeyAccess
{
    public function __construct(
        private string $namespace,
    ) {}

    /**
     * Reads every field path from the item, like `$item['orderItem']['id']`. A nullable field that is
     * selected with `@throwWhenNull` throws `NodeNotFoundException` when it is null, like its getter
     * does, before the key is read from it: `($item['orderItem'] ?? throw ...)['id']`.
     *
     * The expressions are evaluated in order, within one statement, so a field that an earlier
     * expression already checked is not checked again.
     *
     * @param list<array{list<string>, array<int, string>}> $keys Every field path, with the type owning
     *                                                            each nullable field by its index in the path
     * @return list<string>
     */
    public function build(CodeGenerator $generator, string $variable, array $keys): array
    {
        $checked = [];
        $accesses = [];

        foreach ($keys as [$fieldPath, $throwWhenNull]) {
            $access = $variable;
            $path = '';

            foreach ($fieldPath as $index => $fieldName) {
                $access .= sprintf('[%s]', var_export($fieldName, true));
                $path .= '.' . $fieldName;

                if (!isset($throwWhenNull[$index]) || isset($checked[$path])) {
                    continue;
                }

                $checked[$path] = true;
                $access = sprintf(
                    '(%s ?? throw %s::create(%s, %s))',
                    $access,
                    $generator->import(ltrim($this->namespace . '\NodeNotFoundException', '\\')),
                    var_export($throwWhenNull[$index], true),
                    var_export($fieldName, true),
                );
            }

            $accesses[] = $access;
        }

        return $accesses;
    }
}
