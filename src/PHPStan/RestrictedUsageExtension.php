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
use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
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
            $generated = $this->getGeneratedAttribute($classReflection);

            if ($generated?->restrictInstantiation === false) {
                return null;
            }
        }

        return $this->isAllowed(
            $classReflection,
            $scope,
            rtrim($location->createMessage(sprintf(
                '%s is only allowed to be used from within',
                $classReflection->getDisplayName(),
            )), '.'),
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

        $generated = $this->getGeneratedAttribute($reflection);

        if ($generated === null) {
            return null;
        }

        if ( ! $generated->restricted) {
            return null;
        }

        if ($generated->source === $sourceReflection->getName()) {
            return null;
        }

        return RestrictedUsage::create(
            sprintf('%s %s', $errorMessage, $generated->source),
            $identifier,
        );
    }

    private function getGeneratedAttribute(ClassReflection $classReflection) : ?Generated
    {
        foreach ($classReflection->getAttributes() as $attribute) {
            if ($attribute->getName() !== Generated::class) {
                continue;
            }

            $source = $attribute->getArgumentTypes()['source']->getConstantScalarValues()[0];

            if ( ! is_string($source)) {
                continue;
            }

            $restricted = false;

            if (array_key_exists('restricted', $attribute->getArgumentTypes())) {
                $restricted = $attribute->getArgumentTypes()['restricted']->getConstantScalarValues()[0];

                if ( ! is_bool($restricted)) {
                    continue;
                }
            }

            $restrictInstantiation = false;

            if (array_key_exists('restrictInstantiation', $attribute->getArgumentTypes())) {
                $restrictInstantiation = $attribute->getArgumentTypes()['restrictInstantiation']->getConstantScalarValues()[0] ?? false;

                if ( ! is_bool($restrictInstantiation)) {
                    continue;
                }
            }

            return new Generated(
                $source,
                $restricted,
                $restrictInstantiation,
            );
        }

        return null;
    }
}
