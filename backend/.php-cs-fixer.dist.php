<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'               => true,
        '@Symfony:risky'         => true,
        'declare_strict_types'   => true,
        'strict_param'           => true,
        'array_syntax'           => ['syntax' => 'short'],
        'ordered_imports'        => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'      => true,
        'phpdoc_order'           => true,
        'global_namespace_import' => [
            'import_classes'   => true,
            'import_functions' => false,
            'import_constants' => false,
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
