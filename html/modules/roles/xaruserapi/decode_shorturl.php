<?php
/**
 * Extract function and arguments from short URLs for this module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * Supported URLs :
 *
 * /roles/
 * /roles/123
 * /roles/account
 * /roles/account/[module]
 * /roles/list
 * /roles/list/viewall
 * /roles/list/X
 * /roles/list/viewall/X
 * /roles/password
 * /roles/settings
 * /roles/settings/form (deprecated)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author the roles module development team
 * @param $params array containing the different elements of the virtual path
 * @returns array
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function roles_userapi_decode_shorturl($params)
{
    // Initialise the argument list we will return
    $args = array();

    // Analyse the different parts of the virtual path
    // $params[1] contains the first part after index.php/roles

    // In general, you should be strict in encoding URLs, but as liberal
    // as possible in trying to decode them...

    if (empty($params[1])) {
        // nothing specified -> we'll go to the main function
        return array('main', $args);

    } elseif (preg_match('/^index/i',$params[1])) {
        // some search engine/someone tried using index.html (or similar)
        // -> we'll go to the main function
        return array('main', $args);

    } elseif (preg_match('/^list/i',$params[1])) {
        // something that starts with 'list' is probably for the view function
        // Note : make sure your encoding/decoding is consistent ! :-)
        if (!empty($params[2]) && preg_match('/^viewall/i',$params[2],$matches)) {
            $args['phase'] = 'viewall';
            if (!empty($params[3]) && preg_match('/^(\w+)/i',$params[3],$matches)) {
                $args['letter'] = $matches[1];
            }
        } elseif (!empty($params[2]) && preg_match('/^(\w+)/i',$params[2],$matches)) {
            $args['letter'] = $matches[1];
        }
        return array('view', $args);

    } elseif (preg_match('/^password/i',$params[1])) {
        return array('lostpassword', $args);

    } elseif (preg_match('/^account/i',$params[1])) {
        if (!empty($params[2]) && preg_match('/^(\w+)/i',$params[2],$matches)) {
            // Note: this handles usermenu requests for hooked modules (including roles itself)
            $args['moduleload'] = $matches[1];
        }
        return array('account', $args);


    } elseif (preg_match('/^settings/i',$params[1])) {
        if (!empty($params[2]) && preg_match('/^form/i',$params[2],$matches)) {
            // Note : this URL format is no longer in use
            $args['phase'] = 'form';
        }
        return array('usermenu', $args);

    } elseif (preg_match('/^(\d+)/',$params[1],$matches)) {
        // something that starts with a number must be for the display function
        // Note : make sure your encoding/decoding is consistent ! :-)
        $id = $matches[1];
        $args['id'] = $id;
        return array('display', $args);

    } else {
        // the first part might be something variable like a category name
        // In order to match that, you'll have to retrieve all relevant
        // categories for this module, and compare against them...
        // $cid = xarModVars::get('example','mastercids');
        // if (xarModAPILoad('categories','user')) {
        //     $cats = xarModAPIFunc('categories',
        //                          'user',
        //                          'getcat',
        //                          array('cid' => $cid,
        //                                'return_itself' => true,
        //                                'getchildren' => true));
        //     // lower-case for fanciful search engines/people
        //     $params[1] = strtolower($params[1]);
        //     $foundcid = 0;
        //     foreach ($cats as $cat) {
        //         if ($params[1] == strtolower($cat['name'])) {
        //             $foundcid = $cat['cid'];
        //             break;
        //         }
        //     }
        //     // check if we found a matching category
        //     if (!empty($foundcid)) {
        //         $args['cid'] = $foundcid;
        //         // TODO: now analyse $params[2] for index, list, \d+ etc.
        //         // and return array('whatever', $args);
        //     }
        // }

        // we have no idea what this virtual path could be, so we'll just
        // forget about trying to decode this thing

        // you *could* return the main function here if you want to
        // return array('main', $args);
    }

    // default : return nothing -> no short URL decoded
}

?>
