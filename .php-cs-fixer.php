<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('.phpunit.cache')
;

$config = new PhpCsFixer\Config();

return $config
    ->setUsingCache(false)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder)
    ->setLineEnding("\r\n")
;
