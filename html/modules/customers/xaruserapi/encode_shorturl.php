<?php
/**
 * File: $Id$
 *
 * Return the path for a short URL to xarModURL
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * return the path for a short URL to xarModURL for this module
 *
 * Supported URLs :
 *
 * /roles/
 *
 * @author the roles module development team
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function foo_userapi_encode_shorturl($args)
{
    // Get arguments from argument array
    extract($args);

    // Check if we have something to work with
    if (!isset($func)) {
        return;
    }
    unset($args['func']);

    // Initialise the path.
    $path = array();

    // we can't rely on xarModGetName() here -> you must specify the modname.
    $module = 'foo';

    switch($func) {
        case 'main':
            // Note : if your main function calls some other function by default,
            // you should set the path to directly to that other function
            break;
        case 'view':
            $path[] = 'list';
            if (!empty($phase) && $phase == 'viewall') {
                unset($args['phase']);
                $path[] = 'viewall';
            }
            if (!empty($letter)) {
                unset($args['letter']);
                $path[] = $letter;
            }
            break;

        case 'usermenu':
            $path[] = 'settings';
            if (!empty($phase) && ($phase == 'formbasic' || $phase == 'form')) {
                // Note : this URL format is no longer in use
                unset($args['phase']);
                $path[] = 'form';
            }
            break;
    }


    // If no short URL path was obtained above, then there is no encoding.
    if (empty($path)) {
        // Return without a short URL.
        return;
    }

    // Modify some other module arguments as standard URL parameters.
    // Turn a 'cids' array into a 'catid' string.
    if (!empty($cids) && count($cids) > 0) {
        unset($args['cids']);
        if (!empty($andcids)) {
            $args['catid'] = join('+', $cids);
        } else {
            $args['catid'] = join('-', $cids);
        }
    }

    // Slip the module name or alias in at the start of the path.
    array_unshift($path, $module);

    return array(
        'path' => $path,
        'get' => $args
    );
}

?>