<?php

/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
use Twig\TwigTest;
use Throwable;

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
            new TwigTest('string', function ($value) {
                return is_string($value);
            }),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('xar_set', $this->xar_set(...)),
            new TwigFunction('xar_new', $this->xar_new(...)),
            new TwigFunction('xar_subclass', $this->xar_subclass(...)),
            new TwigFunction('xar_classname', $this->xar_classname(...)),
            // @see https://github.com/umpirsky/twig-php-function/blob/master/src/Umpirsky/Twig/Extension/PhpFunctionExtension.php
            new TwigFunction('xar_ksort', $this->xar_ksort(...)),
            new TwigFunction('xar_json_pretty', $this->xar_json_pretty(...)),
            new TwigFunction('xar_unserialize', $this->xar_unserialize(...)),
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

    public function xar_subclass($object, $classname)
    {
        return is_a($object, $classname);
    }

    public function xar_classname($object, $fqcn = true)
    {
        if (!$fqcn) {
            $matches = explode('\\', $object::class);
            return end($matches);
        }
        return $object::class;
    }

    public function xar_ksort($hash)
    {
        ksort($hash);
        return $hash;
    }

    public function xar_json_pretty($var)
    {
        if (is_string($var)) {
            try {
                $var = json_decode($var, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                // $var = $e->getMessage();
            }
        }
        return json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public function xar_unserialize($var)
    {
        if (is_string($var)) {
            try {
                $value = unserialize($var);
                if ($value !== false) {
                    $var = $value;
                }
            } catch (Throwable $e) {
                // $var = $e->getMessage();
            }
        }
        return $var;
    }
}
