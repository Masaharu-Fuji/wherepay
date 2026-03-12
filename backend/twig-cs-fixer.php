<?php

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;

$finder = new Finder;
$finder->in(__DIR__.'/resources/views');

$config = new Config;
$config->setFinder($finder);

return $config;
