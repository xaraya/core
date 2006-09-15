<?php

/**
 * Interface declaration for classes dealing with sets of variables
 *
 * @todo this interface is simplistic, it probably needs more 
 */
interface IxarVars
{
    static function get       ($scope, $name);
    static function set       ($scope, $name, $value);
    static function delete    ($scope, $name);
}

/**
 * Base class for variable handling in core
 *
 * @package variables
 * @author Marcel van der Boom <mrb@hsdev.com>
 **/
class xarVars
{
    
}
?>