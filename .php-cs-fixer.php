<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'                                    => true,
        '@Symfony'                                  => true,
        '@Symfony:risky'                            => true,
        'declare_strict_types'                      => true,
        'strict_param'                              => true,
        'array_syntax'                              => ['syntax' => 'short'],
        'ordered_imports'                           => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'                         => true,
        'not_operator_with_successor_space'         => false,
        'php_unit_method_casing'                    => ['case' => 'camel_case'],
        'php_unit_set_up_tear_down_visibility'      => true,
        'yoda_style'                                => true,
        'final_class'                               => true,
        'final_internal_class'                      => false,
        'self_static_accessor'                      => true,
        'native_function_invocation'                => [
            'include' => ['@compiler_optimized'],
            'scope'   => 'namespaced',
        ],
    ])
    ->setFinder($finder);
