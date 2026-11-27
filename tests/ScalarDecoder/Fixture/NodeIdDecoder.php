<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture;

use InvalidArgumentException;

/**
 * Turns an id like base64("User:1") into a UserId, and checks that it is of the class the field asks for.
 */
final readonly class NodeIdDecoder
{
    /**
     * @template T of NodeId
     * @param null|class-string<T> $class
     * @throws InvalidArgumentException
     * @return ($class is null ? NodeId : T)
     */
    public function decode(string $value, ?string $class = null): NodeId
    {
        $decoded = base64_decode($value, true);
        [$type, $id] = explode(':', $decoded === false ? '' : $decoded, 2) + [1 => '0'];

        $nodeId = match ($type) {
            'User' => new UserId((int) $id),
            'Project' => new ProjectId((int) $id),
            default => throw new InvalidArgumentException(sprintf('"%s" is no id of a known type.', $value)),
        };

        if ($class !== null && !$nodeId instanceof $class) {
            throw new InvalidArgumentException(sprintf('"%s" is no id of a %s.', $value, $class));
        }

        return $nodeId;
    }
}
