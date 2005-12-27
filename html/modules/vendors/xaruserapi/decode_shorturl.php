<?php
/**
 * File: $Id$
 *
 * Extract function and arguments from short URLs for this module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * Supported URLs :
 *
 * /roles/
 * /roles/123
 *
 * @author the roles module development team
 * @param $params array containing the different elements of the virtual path
 * @returns array
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function foo_userapi_decode_shorturl($params)
{
    // Initialise the argument list we will return
    $args = array();

    // Analyse the different parts of the virtual path
    // $params[1] contains the first part after index.php/roles

    // In general, you should be strict in encoding URLs, but as liberal
    // as possible in trying to decode them...

    if (preg_match('/^tab1/i',$params[1])) {
        // just an example
        return array('main', $args);

    } elseif (preg_match('/^tab2/i',$params[1])) {
        // just an example
        return array('main', $args);

    } else {
        // default action
    }

    // default : return nothing -> no short URL decoded
}

?>
