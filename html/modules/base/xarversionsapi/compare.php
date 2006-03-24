<?php
/**
 * Base User Version management functions
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Base User version management functions
 *
 * Compare two legal-style versions supplied as strings or arrays, to an arbitrary number of levels
 * Usage : $which = xarModAPIFunc('base', 'versions', 'compare', array('ver1'=>$version1, 'ver2'=>$version2));
 * or shortcut $which = xarModAPIFunc('base', 'versions', 'compare', array($version1, $version2));
 *
 * @author Jason Judge
 * @param $args['ver1'] or $args[0] version number 1 (string or array)
 * @param $args['ver2'] or $args[1] version number 2 (string or array)
 * @param $args['levels'] maxiumum levels to compare (default: 0/all levels)
 * @param $args['sep'] level separator character (default: '.')
 * @param $args['normalize'] parse the versions into a standard format 'numeric'/'alpha'/false (default: false/none)
 * @param $args['validate'] validation rule to apply (default: false/none)
 * @param $args['order'] the order in which to compare (number or array)
 * @returns number
 * @return number indicating which version number is the latest
 */
function base_versionsapi_compare($args)
{
    // Indicates which is the latest version: -1, +1 or 0 (neither).
    // Can be limited to a certain number of levels. The defaul 0 levels
    // is limited only by the versions passed in.
    // Versions can be strings ('1.2.3') or arrays (array(1, 2, 3)).
    // See test script for examples: tests/base/version_compare.php

    // Extract the arguments. Prefix unnamed parameters with 'p_'.
    extract($args, EXTR_PREFIX_INVALID, 'p');

    // Flag to enable normalization of version strings.
    if (!isset($normalize)) {
        $normalize = false;
    }

    // Set the order parameter to be an array.
    // The order is optional and allows complex ordering to be achieved.
    // Examples:
    // $order =  1 - standard ordering
    // $order = -1 - reverse ordering
    // $order = array(-1,1) - reverse order the first level and normal order remaining levels
    // zero will allow any order for a level.
    if (isset($order) && !is_array($order)) {
        $order = array($order);
    }

    // Default the version numbers to either a positional
    // parameter value or to '0' if nothing passed in at all.
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

    // Quote the separator for use in the cleanup preg.
    $sep2 = preg_quote($sep);

    // Get the number of levels to check.
    if (!settype($levels, 'integer') || $levels < 0) {
        $levels = 0;
    }

    if (isset($validate)) {
        if (!xarModAPIfunc('base', 'versions', 'validate', array('ver'=>$ver1, 'rule'=>$validate))
            || !xarModAPIfunc('base', 'versions', 'validate', array('ver'=>$ver2, 'rule'=>$validate))
        ) {
            return false;
        }
    }

    if ($normalize) {
        list($ver1, $ver2) = xarModAPIfunc('base', 'versions', 'normalize',
            array('sep'=>$sep, 'rule'=>$normalize, 'vers'=>array($ver1, $ver2))
        );
    }

    // Explode the strings into arrays for comparing, if not already done.
    if (!is_array($ver1)) {$ver1 = explode($sep, $ver1);}
    if (!is_array($ver2)) {$ver2 = explode($sep, $ver2);}

    // Get the highest number of levels in a version.
    $limitlevels = max(count($ver1), count($ver2));
    
    // If limited by the calling routine, then cut it down to size.
    if ($levels > 0 && $limitlevels > $levels) {
        $limitlevels = $levels;
    }

    // Default return if no differences are found.
    $latest = 0;
    $levelorder = 1;

    // Loop through each level to find out which is the latest.
    // It's all validation up to this point;)
    for ($i=0; $i<$limitlevels; $i++)
    {
        // Get the ordering for this level.
        $levelorder = (isset($order[$i]) && is_numeric($order[$i])) ? gmp_sign($order[$i]) : $levelorder;

        if ($levelorder <> 0)
        {
            // If either array has run out of elements, then it is
            // the shorter and therefore the lower version.
            if (!isset($ver2[$i])) {$latest = (-1); break;}
            if (!isset($ver1[$i])) {$latest = (+1); break;}

            // Note, we are comparing strings, BUT if both values happen
            // to be numeric, then PHP will do a numeric comparison.
            // PHP's behaviour saves us some work type-casting.
            if ($ver1[$i] > $ver2[$i]) {$latest = (-1); break;}
            if ($ver1[$i] < $ver2[$i]) {$latest = (+1); break;}
        }
    }
    
    // Set the order.
    $latest = $latest * $levelorder;

    return $latest;
}

?>