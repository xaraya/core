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
 * @author the Example module development team
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function roles_userapi_encode_shorturl($args)
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
    $module = 'roles';

    // specify some short URLs relevant to your module
    if ($func == 'main') {
        $path = '/' . $module . '/';

        // Note : if your main function calls some other function by default,
        // you should set the path to directly to that other function

    } elseif ($func == 'view') {
        $path = '/' . $module . '/list';
        if(!empty($phase) && $phase == 'viewall') {
            $path = $path . '/viewall';
        }
        if(!empty($letter)) {
            $path = $path . '/' . $letter;
        }
    } elseif ($func == 'lostpassword') {
        $path = '/' . $module . '/password';

    } elseif ($func == 'showloginform') {
        $path = '/' . $module . '/login';

    } elseif ($func == 'account') {
        $path = '/' . $module . '/account';

    } elseif ($func == 'terms') {
        $path = '/' . $module . '/terms';

    } elseif ($func == 'privacy') {
        $path = '/' . $module . '/privacy';

    } elseif ($func == 'logout') {
        $path = '/' . $module . '/logout';

    } elseif ($func == 'usermenu') {
        $path = '/' . $module . '/settings';
        if(!empty($phase) && $phase == 'formbasic') {
            $path = $path . '/form';
        }
/*
    } elseif ($func == 'register') {
        $path = '/' . $module . '/register.html';
        if (isset($phase)) {
            $path = '/' . $module . '/' . $phase . '.html';
        }
*/
    } elseif ($func == 'display') {
        // check for required parameters
        if (isset($uid) && is_numeric($uid)) {
            $path = '/' . $module . '/' . $uid;
        }
    }

    // add some other module arguments as standard URL parameters
    if (!empty($path)) {
        if (isset($startnum)) {
            $path .= $join . 'startnum=' . $startnum;
            $join = '&';
        }
        if (!empty($catid)) {
            $path .= $join . 'catid=' . $catid;
            $join = '&';
        } elseif (!empty($cids) && count($cids) > 0) {
            if (!empty($andcids)) {
                $catid = join('+',$cids);
            } else {
                $catid = join('-',$cids);
            }
            $path .= $join . 'catid=' . $catid;
            $join = '&';
        }
    }

    return $path;
}

?>
