<?php
/**
 * PHP Version Compatibility Loader
 * 
 * @package PHP Version Compatibility Library
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Paul Crovella
 */
class xarPHPCompat
{
    /**
     * Loads given constants and functions not in the current PHP version
     * 
     * @access public
     * @param  string $path Path to the phpcompat libraries
     */
    function loadAll ($path)
    {
        xarPHPCompat::loopDir($path . '/stubs/', 'loadFunction');
        xarPHPCompat::loopDir($path . '/constants/', 'loadConstant');
    }

    /**
     * Loops over a directory applying a loader to each php file
     * 
     * @access private
     * @param  string $directory The directory to loop over
     * @param  string $manipulator The xarPHPCompat loader to apply to each .php file
     * @todo   use directory iterator?
     */
    function loopDir ($directory, $manipulator)
    {
        $dir = dir($directory);
        while ($file = $dir->read()) {
            if ($pos = strpos($file, '.php')) {
                $loadee = substr($file, 0, $pos);
                xarPHPCompat::$manipulator($directory, $loadee);
            }
        }
        $dir->close();
    }

    /**
     * Loads a workalike function
     * 
     * @access private
     * @param  string $path Path to function
     * @param  string $function Name of function to load
     */
    function loadFunction ($path, $function)
    {
        if (!function_exists($function)) {
            // FIXME: i blindly did a sys::import here, 
            // but PHPCompat isnt loaded by 2.x so no idea what consequences this will have if we do load it
            $dp = str_replace('includes/','',$path);
            $dp = str_replace('/','.',$dp);
            sys::import($dp.'.'.$function);
        }
    }

    /**
     * Loads a workalike constant
     * 
     * @access private
     * @param  string $path Path to constant
     * @param  string $constant Constant to load
     */
    function loadConstant ($path, $constant)
    {
        if (!defined($constant)) {
            // FIXME: i blindly did a sys::import here, 
            // but PHPCompat isnt loaded by 2.x so no idea what consequences this will have if we do load it
            $dp = str_replace('includes/','',$path);
            $dp = str_replace('/','.',$dp);
            sys::import($dp.'.'.$constant);
        }
    }
}
?>
