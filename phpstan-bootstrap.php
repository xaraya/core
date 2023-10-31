<?php
require_once __DIR__ . '/vendor/autoload.php';
global $systemConfiguration;
$systemConfiguration = [];
$systemConfiguration['rootDir'] = __DIR__ . '/';
$systemConfiguration['webDir'] = 'html/';
$systemConfiguration['libDir'] = 'html/lib/';
$systemConfiguration['codeDir'] = 'html/code/';
set_include_path($systemConfiguration['rootDir'] . PATH_SEPARATOR . get_include_path());
sys::init();
