<?php
/**
 * Encode Base module URLS
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
 * return the path for a short URL to xarModURL for this module
 *
 * @author mikespub
 * @param $args the function and arguments passed to xarModURL
 * @return string Path to be added to index.php for a short URL, or empty if failed
 */
function base_userapi_encode_shorturl($args)
{
    // Get arguments from argument array
    extract($args);

    // Check if we have something to work with
    if (!isset($func)) {return;}

    $path = array();
    $get = $args;

    // This module name.
    $module = 'base';

    // Start the path with the module name
    // TODO: support module aliases - allow the page name to be an alias
    $path[] = $module;
    
    if ($func == 'main') {
        // Consume the 'func' parameter.
        unset($get['func']);

        if (isset($page)) {
            // A page name has been passed in - consume it and add it to the path.
            unset($get['page']);
            $path[] = $page;
        }
    }
    
    // Any GET parameters in the args that have not been consumed, will
    // be passed back in the 'get' array, and so will be added to the
    // end of the URL.

    return array('path' => $path, 'get' => $get);
}

?>