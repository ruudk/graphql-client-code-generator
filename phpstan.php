<?php declare(strict_types=1);

return [
    'parameters' => [
        'level' => '9',
        'checkMissingCallableSignature' => true,
        'checkBenevolentUnionTypes' => true,
        'checkMissingOverrideMethodAttribute' => true,
        'reportUnmatchedIgnoredErrors' => true,
        'reportPossiblyNonexistentConstantArrayOffset' => true,

        // Analysis settings
        'paths' => [
            __DIR__ . '/examples',
            __DIR__ . '/src',
            __DIR__ . '/tests',
            __DIR__ . '/phpstan.php',
        ],
        'excludePaths' => [
            'analyseAndScan' => [
                __DIR__ . '/tests/*/Actual/*',
            ],
        ],
        'tips' => [
            'treatPhpDocTypesAsCertain' => false,
        ],
        'todo_by' => [
            'ticket' => [
                'enabled' => true,
                'tracker' => 'github',
            ]
        ],
        'shipmonkDeadCode' => [
            'detect' => [
                'deadEnumCases' => true,
            ],
        ],
        'ignoreErrors' => [
            [
                'identifiers' => [
                    'shipmonk.deadConstant',
                    'shipmonk.deadMethod',
                ],
                'paths' => [
                    __DIR__ . '/examples/Generated/*',
                    __DIR__ . '/tests/*/Expected/*',
                ],
            ],
        ],

        // Developer experience
        'errorFormat' => 'ticketswap',
        'editorUrl' => 'phpstorm://open?file=%%file%%&line=%%line%%',
    ],
];
