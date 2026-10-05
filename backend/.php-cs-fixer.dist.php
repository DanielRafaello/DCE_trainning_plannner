<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// where to clean up
$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/app',
        __DIR__ . '/public',
        __DIR__ . '/filesystem',
        __DIR__ . '/migrations',
        __DIR__ . '/tests',
    ])
    ->append([
        __DIR__ . '/cli-config.php',
        __DIR__ . '/migrations.php',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)                           //ingore files starting with .
    ->ignoreVCS(true);                                 //ignore version control system -> .git


// set rules
return (new Config())
    ->setRiskyAllowed(true) // Has to be true to 'declare_strict_types' be functional
    ->setRules([
        '@PSR12' => true,                                        // PSR-12 coding standard
        'declare_strict_types' => true,                          // vždy declare(strict_types=1)
        'void_return' => true,
        'array_syntax' => ['syntax' => 'short'],                 // array ->  []
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'blank_line_before_statement' => [
            'statements' => ['return'],                           // Add empty line before 'return' (for better readibility)
        ],
        'cast_spaces' => ['space' => 'single'],                  //Unify gaps in type conversion (int) $var
        'concat_space' => ['spacing' => 'one'],                  // Enforce spaces around the period when concatenating strings: $a . $b
        'lowercase_cast' => true,
        'native_function_casing' => true,                         // fix f.e. STRLEN() to strlen()
    ])
    ->setFinder($finder);




/*
 *

 */
