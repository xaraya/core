<?php
/**
 * Get the group that users are members of by default
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
 * getdefaultgroup - get the group that users are members of by default
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return string groupname
 */
function roles_userapi_getdefaultgroup()
{
    $authmodule = xarModGetVar('roles','defaultauthmodule');
    $regmodule = xarModGetVar('roles','defaultregmodule');

    //<jojodee> This is the only relevant one afaik
    //former vars were needed in the intial split with registration and authentication in the 'old' authentication module
    $defaultrole = xarModGetVar('roles', 'defaultgroup');
    if (isset($defaultrole) && !empty($defaultrole)) {
        $defaultgroup = $defaultrole;

    } else {
    // TODO: improve on this hardwiring
        $defaultgroup = 'Users';
    }
    return $defaultgroup;
}

?>