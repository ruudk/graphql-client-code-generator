<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan;

use Override;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedFrom;
use Ruudk\GraphQLCodeGenerator\Config\ConfigException;
use Ruudk\GraphQLCodeGenerator\Config\ConfigLoader;

final readonly class RestrictedUsageExtension implements RestrictedClassNameUsageExtension, RestrictedPropertyUsageExtension, RestrictedMethodUsageExtension
{
    /**
     * @var list<string>
     */
    private array $namespaces;

    /**
     * @param list<string> $configs
     * @throws ConfigException
     */
    public function __construct(
        array $configs,
    ) {
        $this->namespaces = array_map(
            fn($config) => $config->namespace,
            ConfigLoader::load(...$configs),
        );
    }

    #[Override]
    public function isRestrictedClassNameUsage(
        ClassReflection $classReflection,
        Scope $scope,
        ClassNameUsageLocation $location,
    ) : ?RestrictedUsage {
        if ($location->value === ClassNameUsageLocation::INSTANTIATION) {
            foreach ($this->namespaces as $namespace) {
                $pattern = sprintf('/^%s\\\(Query|Mutation)\\\Inline\w{6}(Query|Mutation)$/', preg_quote($namespace, '/'));

                if (preg_match($pattern, $classReflection->getName()) === 1) {
                    return null;
                }
            }
        }

        return $this->isAllowed(
            $classReflection,
            $scope,
            $location->createMessage(sprintf(
                '%s is only allowed to be used from within',
                $classReflection->getDisplayName(),
            )),
            $location->createIdentifier('graphql.inline.class.restricted'),
        );
    }

    #[Override]
    public function isRestrictedMethodUsage(
        ExtendedMethodReflection $methodReflection,
        Scope $scope,
    ) : ?RestrictedUsage {
        if ($methodReflection->getName() === '__construct') {
            return null;
        }

        return $this->isAllowed(
            $methodReflection->getDeclaringClass(),
            $scope,
            sprintf(
                'Method %s from %s is only allowed to be used from within',
                $methodReflection->getName(),
                $methodReflection->getDeclaringClass()->getDisplayName(),
            ),
            'graphql.inline.method.restricted',
        );
    }

    #[Override]
    public function isRestrictedPropertyUsage(
        ExtendedPropertyReflection $propertyReflection,
        Scope $scope,
    ) : ?RestrictedUsage {
        return $this->isAllowed(
            $propertyReflection->getDeclaringClass(),
            $scope,
            sprintf(
                'Property %s from %s is only allowed to be used from within',
                $propertyReflection->getName(),
                $propertyReflection->getDeclaringClass()->getDisplayName(),
            ),
            'graphql.inline.property.restricted',
        );
    }

    private function isAllowed(
        ClassReflection $reflection,
        Scope $scope,
        string $errorMessage,
        string $identifier,
    ) : ?RestrictedUsage {
        if ( ! $scope->isInClass()) {
            return null;
        }

        $sourceReflection = $scope->getClassReflection();

        if ($sourceReflection === null) {
            return null;
        }

        // Check if target is generated
        if ( ! array_any($this->namespaces, fn($namespace) => str_starts_with($reflection->getName(), $namespace))) {
            return null;
        }

        // Generated code can always access other generated code.
        if (array_any($this->namespaces, fn($namespace) => str_starts_with($sourceReflection->getName(), $namespace))) {
            return null;
        }

        $source = null;
        foreach ($reflection->getNativeReflection()->getAttributes() as $attribute) {
            if ($attribute->getName() !== GeneratedFrom::class) {
                continue;
            }

            $source = $attribute->getArguments()['source'];

            break;
        }

        if ( ! is_string($source)) {
            return null;
        }

        if ($sourceReflection->getName() === $source) {
            return null;
        }

        return RestrictedUsage::create(
            sprintf('%s %s', $errorMessage, $source),
            $identifier,
        );
    }
}
