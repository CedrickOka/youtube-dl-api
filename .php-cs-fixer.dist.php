<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('docker')
    ->exclude('phpstan')
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
