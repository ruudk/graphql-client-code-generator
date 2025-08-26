<?php

declare(strict_types=1);

return [
    'includes' => [
        'phar://phpstan.phar/conf/bleedingEdge.neon',
    ],
    'parameters' => [
        'level' => 'max',
        'checkMissingCallableSignature' => true,
        'checkBenevolentUnionTypes' => true,
        'checkMissingOverrideMethodAttribute' => true,
        'reportUnmatchedIgnoredErrors' => true,
        'checkUninitializedProperties' => false,
        'rememberPossiblyImpureFunctionValues' => false,
        'reportPossiblyNonexistentGeneralArrayOffset' => false,
        'exceptions' => [
            'check' => [
                'missingCheckedExceptionInThrows' => true,
                'tooWideThrowType' => true,
            ],
        ],

        // Analysis settings
        'paths' => [
            __DIR__ . '/bin/graphql-client-code-generator',
            __DIR__ . '/examples',
            __DIR__ . '/src',
            __DIR__ . '/tests',
            __DIR__ . '/phpstan.php',
        ],
        'tips' => [
            'treatPhpDocTypesAsCertain' => false,
        ],
        'todo_by' => [
            'ticket' => [
                'enabled' => true,
                'tracker' => 'github',
            ],
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
                    __DIR__ . '/tests/*/Generated/*',
                ],
            ],
            [
                'identifiers' => [
                    'shipmonk.deadConstant',
                ],
                'paths' => [
                    __DIR__ . '/examples/Generated/*',
                ],
            ],
            [
                'identifiers' => [
                    'missingType.checkedException',
                ],
                'paths' => [
                    __DIR__ . '/examples/Generated/*',
                    __DIR__ . '/tests/*',
                ],
            ],
        ],

        // Developer experience
        'errorFormat' => 'ticketswap',
        'editorUrl' => 'phpstorm://open?file=%%file%%&line=%%line%%',
    ],
];
