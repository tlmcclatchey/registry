<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        '@PHP83Migration' => true, // safe even if you require 8.4; keeps forward compatibility tidy
        '@PHPUnit100Migration:risky' => true,

        // Basics / readability
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'single_quote' => true,
        'ternary_operator_spaces' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],

        // Docblocks (keep them clean; don’t fight phpdoc too hard)
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_trim' => true,
        'phpdoc_no_empty_return' => false,

        // Modern stuff
        'native_function_invocation' => false,
        'strict_param' => true,

        // Risky (but useful)
        'modernize_strpos' => true,
        'mb_str_functions' => false,
    ]);
