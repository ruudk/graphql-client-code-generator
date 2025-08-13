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
        ],
        'tips' => [
            'treatPhpDocTypesAsCertain' => false,
        ],

        // Developer experience
        'errorFormat' => 'ticketswap',
        'editorUrl' => 'phpstorm://open?file=%%file%%&line=%%line%%',
    ],
];
