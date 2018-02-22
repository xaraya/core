<?php
/**
 * Get a role's user home
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['itemid']
 * @return string representing the user home
 */
function roles_userapi_getuserhome(Array $args=array())
{
    extract($args);

    if (!empty($itemid) && !is_numeric($itemid)) {
        throw new VariableValidationException(array('itemid',$itemid,'numeric'));
    }

    // the last resort admin always goes to the base main page
    $lastresortvalue = xarModVars::get('privileges','lastresort');
    $userhome = !empty($lastresort) ? '[base]' : xarModUserVars::get('roles','userhome',$itemid);

    // otherwise look for the role's userhome
    if (empty($userhome) || ($userhome == 'undefined')) {
        $notdone = true;
        $userhome = "";
        try {
            $settings = explode(',',xarModVars::get('roles', 'duvsettings'));
            if (in_array('primaryparent', $settings)) {
                // go for the primary parent's userhome
                $parentid = xarModItemVars::get('roles','primaryparent',$itemid);
                if (!empty($parentid)) {
                   return xarMod::apiFunc('roles','user','getuserhome',array('itemid' => $parentid));
                }
            }
        } catch (Exception $e) {}
        if ($notdone) {
           // take the first userhome url encountered.
           // TODO: what would be a more logical choice?
            $role = xarRoles::get($itemid);
            foreach ($role->getParents() as $parent) {
                return xarMod::apiFunc('roles','user','getuserhome',array('itemid' => $parent->getID()));
                break;
            }
        }
    }
    return $userhome;
}
?>
