<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * return the path for a short URL to xarModURL for this module
 *
 * @author mikespub
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function base_userapi_encode_shorturl($args)
{
    // Get arguments from argument array
    extract($args);

    // Check if we have something to work with
    if (!isset($func)) {
        return;
    }

    // Note : make sure you don't pass the following variables as arguments in
    // your module too - adapt here if necessary

    // default path is empty -> no short URL
    $path = '';
    // if we want to add some common arguments as URL parameters below
    $join = '?';
    // we can't rely on xarModGetName() here -> you must specify the modname !
    $module = 'base';

    // specify some short URLs relevant to your module
    if ($func == 'main') {
        // check for required parameters
        if (!empty($page) && is_string($page)) {
            $path = '/' . $module . '/' . rawurlencode($page);
        } else {
            $path = '/' . $module . '/';
        }
    } else {
        // anything else that you haven't defined a short URL equivalent for
        // -> don't create a path here
    }

    return $path;
}

?>