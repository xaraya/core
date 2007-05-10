<?php
/**
 * Get a role's user home
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param int $itemid whether
 * @return string representing the user home
 */
function roles_userapi_getuserhome($args)
{
    extract($args);

    if (!empty($itemid) && !is_numeric($itemid)) {
        throw new VariableValidationException(array('itemid',$itemid,'numeric'));
    }

    // the last resort admin always goes to the base main page
    $lastresortvalue=xarModVars::get('privileges','lastresort');
    $userhome = !empty($lastresort) ? '[base]' : xarModGetUserVar('roles','userhome',$itemid);

    // otherwise look for the role's userhome
    $notdone = true;
    if (!isset($userhome) || empty($userhome) || ($userhome == 'undefined')) {
        $settings = unserialize(xarModVars::get('roles', 'duvsettings'));
        if (in_array('primaryparent', $settings)) {
            // go for the primary parent's userhome
            $parentid = xarModVars::get('roles','primaryparent',$itemid);
            if (!empty($parentid)) {
               return xarModAPIFunc('roles','user','getuserhome',array('itemid' => $parentid));
            }
    }
    if ($notdone) {
           // take the first userhome url encountered.
           // TODO: what would be a more logical choice?
            $role = xarRoles::get($itemid);
            foreach ($role->getParents() as $parent) {
                return xarModAPIFunc('roles','user','getuserhome',array('itemid' => $parent->getID()));
                break;
            }
        }
    }
    return $userhome;
}
?>
