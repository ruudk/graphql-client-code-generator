<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

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

            $allConfigs[] = $configData;
        }

        return $allConfigs;
    }
}
