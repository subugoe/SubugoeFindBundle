<?php

$config = Symfony\CS\Config\Config::create();
$config
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->getFinder()
    ->files()
    ->in(__DIR__)
    ->exclude('build')
    ->exclude('cache')
    ->exclude('vendor')
    ->exclude('tests')
    ->name('*.php');

return $config;
