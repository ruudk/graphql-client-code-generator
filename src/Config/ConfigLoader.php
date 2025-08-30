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
    public static function load(string ...$paths) : array
    {
        $allConfigs = [];
        foreach ($paths as $configFile) {
            $configPath = $configFile;

            if ( ! file_exists($configPath)) {
                throw new ConfigException(sprintf('Configuration file %s does not exist', $configFile));
            }

            try {
                $configData = require $configPath;
            } catch (Throwable $error) {
                throw new ConfigException(sprintf('Failed to load configuration from %s: %s', $configFile, $error->getMessage()));
            }

            if ( ! $configData instanceof Config && ! is_array($configData)) {
                throw new ConfigException(sprintf('Failed to load configuration from %s: expected Config object or array of Config objects', $configFile));
            }

            // Add configs to our collection
            if ($configData instanceof Config) {
                $allConfigs[] = $configData;

                continue;
            }

            // Validate array items
            foreach ($configData as $index => $configItem) {
                if ( ! $configItem instanceof Config) {
                    throw new ConfigException(sprintf('Invalid configuration at index %d in %s: expected Config object', $index, $configFile));
                }

                $allConfigs[] = $configItem;
            }
        }

        return $allConfigs;
    }
}
