<?php

/**
 * File: $Id: s.xaruser.php 1.16 03/04/07 04:30:01-04:00 johnny@falling.local.lan $
 *
 * Base User version management functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Jason Judge
 * @todo none
 */

/**
 * Compare two legal-style versions supplied as strings or arrays, to an arbitrary number of levels
 * Usage : $which = xarModAPIFunc('base', 'versions', 'compare', array('ver1'=>$version1, 'ver2'=>$version2));
 * or shortcut $which = xarModAPIFunc('base', 'versions', 'compare', array($version1, $version2));
 *
 * @author Jason Judge
 * @param $args['ver1'] or $args[0] version number 1 (string or array)
 * @param $args['ver2'] or $args[1] version number 2 (string or array)
 * @param $args['levels'] maxiumum levels to compare (default: all levels)
 * @param $args['strict'] indicates strict numeric-only comparisons (default: true)
 * @param $args['sep'] level separator character (default: .)
 * @returns number
 * @return number indicating which parameter is the latest version
 */
function base_versionsapi_compare($args)
{
    // Indicates which is the latest version: -1, +1 or 0 (neither).
    // Can be limited to a certain number of levels. The defaul 0 levels
    // is limited only by the versions passed in.
    // Versions can be strings ('1.2.3') or arrays (array(1, 2, 3)).
    // See test script for examples: tests/base/version_compare.php

    // Extract the arguments. Allow for positional parameters.
    extract($args, EXTR_PREFIX_INVALID, 'p');

    // Set this flag if checking should be strictly numeric.
    // With strict set (true), non-numeric characters will be stripped prior to
    // the comparison.
    // With strict reset (false), then string comparisons will be
    // performed where one or both version levels are not numeric.
    if (!isset($strict)) {
        $strict = true;
    }

    // Default either version to '0' if not pass in at all.
    if (!isset($ver1)) {
        $ver1 = (isset($p_0) ? $p_0 : '0');
    }

    if (!isset($ver2)) {
        $ver2 = (isset($p_1) ? $p_1 : '0');
    }

    // Default the level separator to '.' if none valid passed in.
    if (!isset($sep) || strlen($sep) <> 1) {
        $sep = '.';
    }

    // Quote the separator for use in the preg cleanup.
    $sep2 = preg_quote($sep);

    // Get the number of levels to check.
    if (!settype($levels, 'integer') || $levels < 0) {
        $levels = 0;
    }

    // If arrays have been passed in, convert them to a legal-format string.
    if (is_array($ver1)) {
        $ver1 = implode($sep, $ver1);
    }

    if (is_array($ver2)) {
        $ver2 = implode($sep, $ver2);
    }

    // Clean up the input strings.
    list($ver1, $ver2) = preg_replace(
        array(
           '/(\s'. ($strict ? '|[^\d'.$sep2.']' : '') .')*/',
           '/^'.$sep2.'/',
           '/'.$sep2.'$/',
           '/'.$sep2.$sep2.'/',
           '/^$/'
        ),
        array('', '0'.$sep, $sep.'0', $sep.'0'.$sep, '0'),
        array($ver1, $ver2)
    );

    // Explode the strings into arrays for comparing.
    $ver1 = explode($sep, $ver1);
    $ver2 = explode($sep, $ver2);

    // Get the highest number of levels in a version.
    $limitlevels = max(count($ver1), count($ver2));
    
    // If limited by the calling routine, then cut it down to size.
    if ($levels > 0 && $limitlevels > $levels) {
        $limitlevels = $levels;
    }

    // Pad out version arrays where necessary.
    while (count($ver1) < $limitlevels) {
        array_push($ver1, '0');
    }

    while (count($ver2) < $limitlevels) {
        array_push($ver2, '0');
    }

    $latest = 0;

    // Loop through each level to find out which is the latest.
    for ($i=0; $i<$limitlevels; $i++)
    {
        // Note, we are comparing strings, BUT if both values happen
        // to be numeric, then PHP will do a numeric comparison.
        // PHP's behaviour saves us some work type-casting.
        if ($ver1[$i] > $ver2[$i]) {$latest = (-1); break;}
        if ($ver2[$i] > $ver1[$i]) {$latest = (+1); break;}
    }
    
    return $latest;
}

?>
