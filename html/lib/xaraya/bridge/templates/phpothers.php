<?php
/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * PHP Functions, Filters, Tests etc.
 */
class PHPOtherExtension extends XarayaTwigExtension
{
    public function getFilters()
    {
        return [
        ];
    }

    public function getTests()
    {
        return [
            // @todo add some simple tests too
            new TwigTest('numeric', function ($value) {
                return is_numeric($value);
            }),
            // @see https://github.com/squirrelphp/twig-php-syntax/blob/master/src/Test/ObjectTest.php
            new TwigTest('object', function ($value) {
                return is_object($value);
            }),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('xar_set', [$this, 'xar_set']),
            new TwigFunction('xar_new', [$this, 'xar_new']),
            // @see https://github.com/umpirsky/twig-php-function/blob/master/src/Umpirsky/Twig/Extension/PhpFunctionExtension.php
            new TwigFunction('xar_ksort', [$this, 'xar_ksort']),
            new TwigFunction('xar_json_pretty', [$this, 'xar_json_pretty']),
            new TwigFunction('xar_unserialize', [$this, 'xar_unserialize']),
        ];
    }

    public function xar_set($object, $propname, $value)
    {
        $object->$propname = $value;
        return $object->$propname;
    }

    public function xar_new($class, ...$args)
    {
        return new $class(...$args);
    }

    public function xar_ksort($hash)
    {
        ksort($hash);
        return $hash;
    }

    public function xar_json_pretty($var)
    {
        if (is_string($var)) {
            $var = json_decode($var, true);
        }
        return json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public function xar_unserialize($var)
    {
        if (is_string($var)) {
            $var = unserialize($var);
        }
        return $var;
    }
}
