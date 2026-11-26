<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use Symfony\Component\Filesystem\Path;
use Throwable;

final readonly class ConfigLoader
{
    /**
     * @throws ConfigException
     * @return list<Config>
     */
    public static function load(string ...$paths): array
    {
        $allConfigs = [];
        foreach ($paths as $configFile) {
            $configPath = $configFile;

            if (!file_exists($configPath)) {
                throw new ConfigException(sprintf('Configuration file %s does not exist', $configFile));
            }

            try {
                $configData = require $configPath;
            } catch (Throwable $error) {
                throw new ConfigException(sprintf(
                    'Failed to load configuration from %s: %s',
                    $configFile,
                    $error->getMessage(),
                ));
            }

            if (!$configData instanceof Config) {
                throw new ConfigException(sprintf(
                    'Failed to load configuration from %s: expected a %s object',
                    $configFile,
                    Config::class,
                ));
            }

            foreach ($allConfigs as $otherFile => $other) {
                if ($otherFile === $configFile) {
                    continue;
                }

                self::assertReadsOtherDirectories($configData, $configFile, $other, $otherFile);
            }

            $allConfigs[$configFile] = $configData;
        }

        return array_values($allConfigs);
    }

    /**
     * Only the schemas of one config know about each other. When two configs read the
     * same directory, each would take the attributes of the other, so that is not allowed.
     *
     * @throws ConfigException
     */
    private static function assertReadsOtherDirectories(
        Config $config,
        string $file,
        Config $other,
        string $otherFile,
    ): void {
        foreach ($config->processingDirectories() as $directory) {
            foreach ($other->processingDirectories() as $otherDirectory) {
                if (
                    Path::isBasePath(Path::canonicalize($directory), Path::canonicalize($otherDirectory))
                    || Path::isBasePath(Path::canonicalize($otherDirectory), Path::canonicalize($directory))
                ) {
                    throw new ConfigException(sprintf(
                        'The configs in %s and %s both read "%s". Add the schemas that read the same directories to one config.',
                        $otherFile,
                        $file,
                        Path::isBasePath(Path::canonicalize($directory), Path::canonicalize($otherDirectory))
                            ? $otherDirectory
                            : $directory,
                    ));
                }
            }
        }
    }
}
