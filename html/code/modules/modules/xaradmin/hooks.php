<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Configure hooks by hook module
 *
 * @author Xaraya Development Team
 * @param $args['curhook'] current hook module (optional)
 * @param $args['return_url'] URL to return to after updating the hooks (optional)
 *
 */
function modules_admin_hooks($args)
{
// Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    if (!xarVarFetch('hook', 'isset', $curhook, '', XARVAR_NOT_REQUIRED)) {return;}
    extract($args);

    // Get the list of all hook modules, and the current hooks enabled for all modules
    $hooklist = xarMod::apiFunc('modules','admin','gethooklist');

    $data = array();
    $data['savechangeslabel'] = xarML('Save Changes');
    $data['hookmodules'] = array();
    $data['hookedmodules'] = array();
    $data['curhook'] = '';
    $data['hooktypes'] = array();
    $data['authid'] = '';

    // via arguments only, for use in BL tags :
    // <xar:module main="false" module="modules" type="admin" func="hooks" curhook="hitcount" return_url="$thisurl"/>
    if (empty($return_url)) $return_url = '';

    $data['return_url'] = $return_url;

    if (!empty($curhook)) {
        // Get list of modules likely to be "interested" in hooks
        $modList = xarMod::apiFunc('modules', 'admin', 'getlist',
                                 array('orderBy' => 'category/name'));
        if (!isset($modList)) return;

        $oldcat = '';
        $deletemod = null;
        for ($i = 0, $max = count($modList); $i < $max; $i++) {
            // CHECKME: don't allow hooking to yourself !?
            if ($modList[$i]['name'] == $curhook) {
                $deletemod = $i;
                continue;
            }

            $modList[$i]['header'] = '';
            $modList[$i]['itemtypes'] = array();
            $modList[$i]['checked'] = array();
            $modList[$i]['links'] = array();

            $modList[$i]['link'] = xarModURL('modules','admin','modifyorder',
                                             array('modulename' => $curhook,
                                                   'modulehookedname' => $modList[$i]['name'] ));

            // Kinda group by category in the display
            if ($oldcat != $modList[$i]['category']) {
                $modList[$i]['header'] = xarVarPrepForDisplay($modList[$i]['category']);
                $oldcat = $modList[$i]['category'];
            }

            // Get the list of all item types for this module (if any)
            try {
                $itemtypes = xarMod::apiFunc($modList[$i]['name'],'user','getitemtypes',array());
            } catch ( FunctionNotFoundException $e) {
                // No worries
            }
            if (isset($itemtypes)) $modList[$i]['itemtypes'] = $itemtypes;

            foreach ($hooklist[$curhook] as $hook => $hookedmods) {
                if (!empty($hookedmods[$modList[$i]['systemid']])) {
                    foreach ($hookedmods[$modList[$i]['systemid']] as $itemType => $val) {
                        // For each itemtype, tick the checked flag.
                        $modList[$i]['checked'][$itemType] = 1;
                        // BEGIN MODIF
                        $modList[$i]['links'][$itemType] = xarModURL('modules','admin','modifyorder',
                                                                     array('modulename' => $curhook,
                                                                           'modulehookedname' =>  $modList[$i]['name'],
                                                                           'itemtype' => $itemType));
                        // END MODIF
                    }
                    break;
                }
            }
        }
        // CHECKME: don't allow hooking to yourself !?
        if (!empty($deletemod)) {
            unset($modList[$deletemod]);
        }
        $data['curhook'] = $curhook;
        $data['hookedmodules'] = $modList;
        $data['authid'] = xarSecGenAuthKey('modules');

        foreach ($hooklist[$curhook] as $hook => $hookedmods) {
            $data['hooktypes'][] = $hook;
        }
    }

    foreach ($hooklist as $hookmodname => $hooks) {

        // Get module display name
        $regid = xarMod::getRegID($hookmodname);
        $modinfo = xarMod::getInfo($regid);
        $data['hookmodules'][] = array('modid' => $regid,
                                       'modname' => $hookmodname,
                                       'modtitle' => $modinfo['description'],
                                       'modstatus' => xarModIsAvailable($modinfo['name']),
                                       'modlink' => xarModURL('modules','admin','hooks',
                                                              array('hook' => $hookmodname)));
    }

    //return the output
    return $data;
}

?>
