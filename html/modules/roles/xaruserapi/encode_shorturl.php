<?php

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
        $path = '/' . $module . '/list.html';

    } elseif ($func == 'lostpassword') {
        $path = '/' . $module . '/password.html';

    } elseif ($func == 'showloginform') {
        $path = '/' . $module . '/login.html';
/*
    } elseif ($func == 'register') {
        $path = '/' . $module . '/register.html';
        if (isset($phase)) {
            $path = '/' . $module . '/' . $phase . '.html';
        }
*/
    } elseif ($func == 'display') {
        // check for required parameters
        if (isset($exid) && is_numeric($exid)) {
            $path = '/' . $module . '/' . $exid . '.html';

            // you might have some additional parameter that you want to use to
            // create different virtual paths here - for example a category name
            // See above for an example...

        } else {
            // we don't know how to handle that -> don't create a path here

            // Note : this generally means that someone tried to create a
            // link to your module, but used invalid parameters for xarModURL
            // -> you might want to provide a default path to return to
            // $path = '/' . $module . '/list.html';
        }

    } else {
        // anything else that you haven't defined a short URL equivalent for
        // -> don't create a path here
    }

    // add some other module arguments as standard URL parameters
    if (!empty($path)) {
        if (isset($startnum)) {
            $path .= $join . 'startnum=' . $startnum;
            $join = '&amp;';
        }
        if (!empty($catid)) {
            $path .= $join . 'catid=' . $catid;
            $join = '&amp;';
        } elseif (!empty($cids) && count($cids) > 0) {
            if (!empty($andcids)) {
                $catid = join('+',$cids);
            } else {
                $catid = join('-',$cids);
            }
            $path .= $join . 'catid=' . $catid;
            $join = '&amp;';
        }
    }

    return $path;
}

?>