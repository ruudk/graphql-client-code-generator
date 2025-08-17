<?php declare(strict_types=1);

return [
    'parameters' => [
        'level' => '9',
        'checkMissingCallableSignature' => true,
        'checkBenevolentUnionTypes' => true,
        'checkMissingOverrideMethodAttribute' => true,
        'reportUnmatchedIgnoredErrors' => false,
        'reportPossiblyNonexistentConstantArrayOffset' => true,

        // Analysis settings
        'paths' => [
            __DIR__ . '/examples',
            __DIR__ . '/src',
            __DIR__ . '/tests',
            __DIR__ . '/phpstan.php',
        ],
        'tips' => [
            'treatPhpDocTypesAsCertain' => false,
        ],
        'ignoreErrors' => [
            // TODO: webonyx/graphql-php#1752
            // https://github.com/webonyx/graphql-php/pull/1752
            '#^Parameter \#2 \$visitor of static method GraphQL\\\\Language\\\\Visitor\:\:visit\(\) expects array\<string, array\<string, callable\(GraphQL\\\\Language\\\\AST\\\\Node\)\: \(GraphQL\\\\Language\\\\VisitorOperation\|void\|false\|null\)\>\|\(callable\(GraphQL\\\\Language\\\\AST\\\\Node\)\: \(GraphQL\\\\Language\\\\VisitorOperation\|void\|false\|null\)\)\>, array\{SelectionSet\: Closure\(GraphQL\\\\Language\\\\AST\\\\Node\)\: \(GraphQL\\\\Language\\\\AST\\\\SelectionSetNode\|null\)\} given\.$#',
        ],
        'todo_by' => [
            'ticket' => [
                'enabled' => true,
                'tracker' => 'github',
            ]
        ],

        // Developer experience
        'errorFormat' => 'ticketswap',
        'editorUrl' => 'phpstorm://open?file=%%file%%&line=%%line%%',
    ],
];
