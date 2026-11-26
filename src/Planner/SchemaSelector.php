<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use InvalidArgumentException;

/**
 * Decides whether a GraphQL block in a file belongs to the schema that is being
 * planned. A block may leave out its schema when only one schema reads the file,
 * and has to name it when more than one does.
 */
final readonly class SchemaSelector
{
    /**
     * @param ?string $schema The name of the schema that is being planned
     * @param list<?string> $readers The names of the schemas that read the file,
     *                               including the one that is being planned
     */
    public function __construct(
        private ?string $schema = null,
        private array $readers = [null],
    ) {}

    /**
     * @param ?string $name The schema the block names, if any
     * @param string $location Where the block is, for the error message
     * @throws InvalidArgumentException
     */
    public function selects(?string $name, string $location): bool
    {
        if ($name === null) {
            if (count($this->readers) > 1) {
                throw new InvalidArgumentException(sprintf(
                    '%s has to name its schema, because the schemas %s read it.',
                    $location,
                    $this->quote($this->readers),
                ));
            }

            return true;
        }

        if (!in_array($name, $this->readers, true)) {
            $named = array_values(array_filter($this->readers, static fn(?string $reader) => $reader !== null));

            throw new InvalidArgumentException(match (count($named)) {
                0 => sprintf('%s names the schema "%s", but the schema that reads it has no name.', $location, $name),
                1 => sprintf(
                    '%s names the schema "%s", but only the schema %s reads it.',
                    $location,
                    $name,
                    $this->quote($named),
                ),
                default => sprintf(
                    '%s names the schema "%s", but only the schemas %s read it.',
                    $location,
                    $name,
                    $this->quote($named),
                ),
            });
        }

        return $name === $this->schema;
    }

    /**
     * @param list<?string> $names
     */
    private function quote(array $names): string
    {
        return implode(', ', array_map(static fn(?string $name) => sprintf('"%s"', $name), $names));
    }
}
