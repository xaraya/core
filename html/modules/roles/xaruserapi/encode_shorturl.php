<?php
/**
 * Return the path for a short URL to xarModURL for this module
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
 * return the path for a short URL to xarModURL for this module
 *
 * Supported URLs :
 *
 * /roles/
 * /roles/123
 * /roles/account
 * /roles/account/[module]
 *
 * /roles/list
 * /roles/list/viewall
 * /roles/list/X
 * /roles/list/viewall/X
 *
 * /roles/login
 * /roles/logout
 * /roles/password
 * /roles/privacy
 * /roles/terms
 *
 * /roles/register
 * /roles/register/registration
 * /roles/register/checkage
 *
 * /roles/settings
 * /roles/settings/form (deprecated)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author the roles module development team
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
    unset($args['func']);

    // Initialise the path.
    $path = array();

    // we can't rely on xarModGetName() here -> you must specify the modname.
    $module = 'roles';

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

        case 'lostpassword':
            $path[] = 'password';
            break;

        case 'showloginform':
            $path[] = 'login';
            break;

        case 'account':
            $path[] = 'account';
            if(!empty($moduleload)) {
                // Note: this handles usermenu requests for hooked modules (including roles itself).
                unset($args['moduleload']);
                $path[] = $moduleload;
            }
            break;

        case 'terms':
        case 'privacy':
        case 'logout':
            $path[] = $func;
            break;

        case 'usermenu':
            $path[] = 'settings';
            if (!empty($phase) && ($phase == 'formbasic' || $phase == 'form')) {
                // Note : this URL format is no longer in use
                unset($args['phase']);
                $path[] = 'form';
            }
            break;

        case 'register':
            $path[] = 'register';
            if (!empty($phase)) {
                // Bug 4404: registerform and registration are aliases.
                if ($phase == 'registerform' || $phase == 'registration' || $phase == 'checkage') {
                    unset($args['phase']);
                    $path[] = ($phase == 'registerform' ? 'registration' : $phase);
                } else {
                    // unsupported phase - must be passed via forms
                }
            }
            break;

        case 'display':
            // check for required parameters
            if (isset($uid) && is_numeric($uid)) {
                unset($args['uid']);
                $path[] = $uid;
            }
            break;

        default:
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
