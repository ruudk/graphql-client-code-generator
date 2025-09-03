<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Executor;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser as GraphQLParser;
use JsonException;
use LogicException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Generator\DataClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\EnumTypeGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\ErrorClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\ExceptionClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\InputTypeGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\NodeNotFoundExceptionGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\OperationClassGenerator;
use Ruudk\GraphQLCodeGenerator\GraphQL\AST\Printer;
use Ruudk\GraphQLCodeGenerator\PHP\Visitor\OperationInjector;
use Ruudk\GraphQLCodeGenerator\PHP\Visitor\UseStatementInserter;
use Ruudk\GraphQLCodeGenerator\Planner\OperationPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\EnumClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\InputClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\NodeNotFoundExceptionPlan;
use Ruudk\GraphQLCodeGenerator\Planner\PlannerResult;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\BackedEnumTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\CollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\IndexByCollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\NullableTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ObjectTypeInitializer;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webmozart\Assert\Assert;

final class PlanExecutor
{
    private readonly DataClassGenerator $dataClassGenerator;
    private readonly EnumTypeGenerator $enumTypeGenerator;
    private readonly OperationClassGenerator $operationClassGenerator;
    private readonly ErrorClassGenerator $errorClassGenerator;
    private readonly ExceptionClassGenerator $exceptionClassGenerator;
    private readonly InputTypeGenerator $inputTypeGenerator;
    private readonly NodeNotFoundExceptionGenerator $nodeNotFoundExceptionGenerator;
    private Parser $phpParser;
    private Filesystem $filesystem;

    public function __construct(
        private Config $config,
    ) {
        // Initialize type initializer
        $typeInitializer = new DelegatingTypeInitializer(
            new NullableTypeInitializer(),
            new IndexByCollectionTypeInitializer(),
            new CollectionTypeInitializer(),
            new BackedEnumTypeInitializer($config->addUnknownCaseToEnums, $config->namespace),
            new ObjectTypeInitializer(),
            ...$config->typeInitializers,
        );

        // Initialize all generators
        $this->dataClassGenerator = new DataClassGenerator($config, $typeInitializer);
        $this->enumTypeGenerator = new EnumTypeGenerator($config);
        $this->operationClassGenerator = new OperationClassGenerator($config);
        $this->errorClassGenerator = new ErrorClassGenerator($config);
        $this->exceptionClassGenerator = new ExceptionClassGenerator($config);
        $this->inputTypeGenerator = new InputTypeGenerator($config);
        $this->nodeNotFoundExceptionGenerator = new NodeNotFoundExceptionGenerator($config);
        $this->phpParser = new ParserFactory()->createForNewestSupportedVersion();
        $this->filesystem = new Filesystem();
    }

    /**
     * @throws JsonException
     * @throws IOException
     * @throws SyntaxError
     * @throws RuntimeException
     * @throws LogicException
     * @return array<string, string>
     */
    public function execute(PlannerResult $plan) : array
    {
        $files = [];

        foreach ($plan->classes as $path => $class) {
            $files[$path] = $this->generateClass($class);
        }

        foreach ($plan->operations as $operation) {
            foreach ($this->generateOperation($operation) as $file => $content) {
                $files[$file] = $content;
            }
        }

        if ($this->config->formatOperationFiles) {
            $finder = Finder::create()->files()
                ->in($this->config->queriesDir)
                ->name('*.graphql')
                ->sortByName();

            foreach ($finder as $file) {
                $document = GraphQLParser::parse($file->getContents());

                $this->filesystem->dumpFile($file->getPathname(), Printer::doPrint($document));
            }
        }

        $printer = new Standard();
        foreach ($plan->operationsToInject as $path => $operations) {
            $oldStmts = $this->phpParser->parse($this->filesystem->readFile($path));
            Assert::notNull($oldStmts, 'Failed to parse PHP file');

            $oldTokens = $this->phpParser->getTokens();

            $newStmts = new NodeTraverser(new CloningVisitor())->traverse($oldStmts);

            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NodeConnectingVisitor());
            $traverser->addVisitor(new OperationInjector($operations));
            $newStmts = $traverser->traverse($newStmts);

            $fqcns = [];
            foreach ($operations as $methodOperations) {
                foreach ($methodOperations as $fqcn) {
                    $fqcns[] = $fqcn;
                }
            }

            $newStmts = new NodeTraverser(
                new NodeConnectingVisitor(),
                new UseStatementInserter($fqcns),
            )->traverse($newStmts);

            $files[$path] = $printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
        }

        return $files;
    }

    /**
     * @throws LogicException
     */
    private function generateClass(object $class) : string
    {
        return match ($class::class) {
            DataClassPlan::class => $this->dataClassGenerator->generate($class),

            EnumClassPlan::class => $this->enumTypeGenerator->generate($class),

            InputClassPlan::class => $this->inputTypeGenerator->generate($class),

            NodeNotFoundExceptionPlan::class => $this->nodeNotFoundExceptionGenerator->generate(),

            default => throw new LogicException('Unknown class type: ' . $class::class),
        };
    }

    /**
     * @throws JsonException
     * @return array<string, string>
     */
    private function generateOperation(OperationPlan $operation) : array
    {
        $files = [];

        // Generate operation class
        $files[$operation->operationClass->path] = $this->operationClassGenerator->generate(
            $operation->operationClass,
        );

        // Generate error class
        $files[$operation->errorClass->path] = $this->errorClassGenerator->generate(
            $operation->errorClass,
        );

        // Generate exception class only if it exists
        if ($operation->exceptionClass !== null) {
            $files[$operation->exceptionClass->path] = $this->exceptionClassGenerator->generate(
                $operation->exceptionClass,
            );
        }

        return $files;
    }
}
