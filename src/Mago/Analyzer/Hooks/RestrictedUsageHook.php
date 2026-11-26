<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago\Analyzer\Hooks;

use Mago\Sdk\Analyzer\AfterFileAnalysisContext;
use Mago\Sdk\Analyzer\AfterFileAnalysisHook;
use Mago\Sdk\Analyzer\Metadata\AttributeMetadata;
use Mago\Sdk\Analyzer\Metadata\ClassLikeMetadata;
use Mago\Sdk\Analyzer\Type\NamedObjectType;
use Mago\Sdk\Exception\CancelledException;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\SourceFile;
use Override;
use Ruudk\GraphQLCodeGenerator\Attribute\Generated;

/**
 * Reports usage of restricted generated classes, their methods and their
 * properties from any class other than the one they were generated for.
 *
 * Mirrors the PHPStan `RestrictedUsageExtension`.
 *
 * @internal
 */
final readonly class RestrictedUsageHook implements AfterFileAnalysisHook
{
    private const array CLASS_LIKE_KINDS = [
        NodeKind::Class_,
        NodeKind::Enum,
        NodeKind::Interface,
        NodeKind::Trait,
    ];

    private const array NAME_KINDS = [
        NodeKind::LocalIdentifier,
        NodeKind::QualifiedIdentifier,
        NodeKind::FullyQualifiedIdentifier,
    ];

    private const array METHOD_CALL_KINDS = [
        NodeKind::MethodCall,
        NodeKind::NullSafeMethodCall,
    ];

    private const array PROPERTY_ACCESS_KINDS = [
        NodeKind::PropertyAccess,
        NodeKind::NullSafePropertyAccess,
    ];

    /**
     * @param list<string> $namespaces namespaces that hold generated code
     */
    public function __construct(
        private array $namespaces,
    ) {}

    #[Override]
    public function getRequirements() : array
    {
        return [];
    }

    /**
     * @throws CancelledException
     */
    #[Override]
    public function afterFileAnalysis(AfterFileAnalysisContext $context) : void
    {
        $source = $context->analysis->getSourceFile();

        $usages = [
            ...$this->findClassNameUsages($source),
            ...$this->findMemberUsages($context, $source),
        ];

        if ($usages === []) {
            return;
        }

        $classNames = array_values(array_unique(array_map(
            static fn(RestrictedUsage $usage) => $usage->class,
            $usages,
        )));
        $generated = [];

        foreach ($context->codebase->getMultipleClassLikes($classNames) as $index => $metadata) {
            $generated[strtolower($classNames[$index])] = $metadata === null
                ? null
                : self::getGeneratedAttribute($metadata);
        }

        $classLikeNames = [];

        foreach ($usages as $usage) {
            $context->cancellation->throwIfCancelled();

            $attribute = $generated[strtolower($usage->class)] ?? null;

            if ($attribute === null || ! $attribute->restricted) {
                continue;
            }

            if ($usage->kind === RestrictedUsageKind::Instantiation && ! $attribute->restrictInstantiation) {
                continue;
            }

            $sourceClass = $this->findEnclosingClassLike($source, $usage->node, $classLikeNames);

            if ($sourceClass === null || $this->isGenerated($sourceClass)) {
                continue;
            }

            if (strcasecmp($attribute->source, $sourceClass) === 0) {
                continue;
            }

            $context->report(
                Level::Error,
                $usage->kind->code(),
                Issue::new(
                    sprintf('%s is only allowed to be used from within %s', $usage->describe(), $attribute->source),
                    $usage->node->span,
                ),
            );
        }
    }

    /**
     * @return list<RestrictedUsage>
     */
    private function findClassNameUsages(SourceFile $source) : array
    {
        $usages = [];

        foreach (self::NAME_KINDS as $kind) {
            foreach ($source->getNodes($kind) as $node) {
                $resolved = $source->getResolvedName($node);

                if ($resolved === null || ! $this->isGenerated($resolved->name)) {
                    continue;
                }

                $instantiation = null;
                $imported = false;

                foreach ($source->getAncestors($node) as $ancestor) {
                    if ($ancestor->kind === NodeKind::Use) {
                        $imported = true;

                        break;
                    }

                    if ($ancestor->kind === NodeKind::Identifier || $ancestor->kind === NodeKind::Expression) {
                        continue;
                    }

                    if ($ancestor->kind === NodeKind::Instantiation) {
                        $instantiation = $ancestor;
                    }

                    break;
                }

                if ($imported) {
                    continue;
                }

                $usages[] = $instantiation !== null
                    ? new RestrictedUsage(RestrictedUsageKind::Instantiation, $instantiation, $resolved->name)
                    : new RestrictedUsage(RestrictedUsageKind::ClassName, $node, $resolved->name);
            }
        }

        return $usages;
    }

    /**
     * @return list<RestrictedUsage>
     */
    private function findMemberUsages(AfterFileAnalysisContext $context, SourceFile $source) : array
    {
        $accesses = [];

        foreach ([...self::METHOD_CALL_KINDS, ...self::PROPERTY_ACCESS_KINDS] as $kind) {
            foreach ($source->getNodes($kind) as $node) {
                [$object, $selector] = $source->getChildren($node) + [null, null];

                if ($object?->kind !== NodeKind::Expression || $selector?->kind !== NodeKind::ClassLikeMemberSelector) {
                    continue;
                }

                $identifier = $source->getChildren($selector)[0] ?? null;

                if ($identifier?->kind !== NodeKind::LocalIdentifier) {
                    continue;
                }

                $member = $source->getText($identifier);

                if (in_array($kind, self::METHOD_CALL_KINDS, true) && strtolower($member) === '__construct') {
                    continue;
                }

                $accesses[] = [
                    in_array($kind, self::METHOD_CALL_KINDS, true)
                        ? RestrictedUsageKind::Method
                        : RestrictedUsageKind::Property,
                    $object,
                    $identifier,
                    $member,
                ];
            }
        }

        if ($accesses === []) {
            return [];
        }

        $types = $context->analysis->getMultipleExpressionTypes(array_map(static fn($access) => $access[1], $accesses));
        $usages = [];

        foreach ($accesses as $index => [$kind, , $identifier, $member]) {
            foreach ($types[$index]->atomicTypes ?? [] as $atomic) {
                if ( ! $atomic instanceof NamedObjectType || ! $this->isGenerated($atomic->name)) {
                    continue;
                }

                $usages[] = new RestrictedUsage($kind, $identifier, $atomic->name, $member);
            }
        }

        return $usages;
    }

    /**
     * @param array<int, null|string> $classLikeNames cache of class-like node ids to their name
     */
    private function findEnclosingClassLike(SourceFile $source, Node $node, array &$classLikeNames) : ?string
    {
        foreach ($source->getAncestors($node) as $ancestor) {
            if ( ! in_array($ancestor->kind, self::CLASS_LIKE_KINDS, true)) {
                continue;
            }

            if ( ! array_key_exists($ancestor->id, $classLikeNames)) {
                $name = null;

                foreach ($source->getChildren($ancestor) as $child) {
                    if ($child->kind === NodeKind::LocalIdentifier) {
                        $name = $source->getResolvedName($child)?->name;

                        break;
                    }
                }

                $classLikeNames[$ancestor->id] = $name;
            }

            return $classLikeNames[$ancestor->id];
        }

        return null;
    }

    private function isGenerated(string $class) : bool
    {
        $class = ltrim($class, '\\');

        return array_any($this->namespaces, static fn($namespace) => str_starts_with(
            strtolower($class),
            strtolower($namespace),
        ));
    }

    private static function getGeneratedAttribute(ClassLikeMetadata $metadata) : ?Generated
    {
        foreach ($metadata->attributes as $attribute) {
            if (strcasecmp(ltrim($attribute->name, '\\'), Generated::class) !== 0) {
                continue;
            }

            $sourceType = $attribute->getArgument(0, 'source')?->valueType;
            $source = $sourceType?->getLiteralString() ?? $sourceType?->getLiteralClassString();

            if ($source === null) {
                continue;
            }

            return new Generated(
                $source,
                self::getBoolArgument($attribute, 1, 'restricted'),
                self::getBoolArgument($attribute, 2, 'restrictInstantiation'),
            );
        }

        return null;
    }

    private static function getBoolArgument(AttributeMetadata $attribute, int $position, string $name) : bool
    {
        return $attribute->getArgument($position, $name)?->valueType?->getLiteralBool() ?? false;
    }
}
