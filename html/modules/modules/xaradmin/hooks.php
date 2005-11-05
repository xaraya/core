<?php
/**
 * Configure hooks by hook module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
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
    $hooklist = xarModAPIFunc('modules','admin','gethooklist');

    $data = array();
    $data['savechangeslabel'] = xarML('Save Changes');
    $data['hookmodules'] = array();
    $data['hookedmodules'] = array();
    $data['curhook'] = '';
    $data['hooktypes'] = array();
    $data['authid'] = '';

    // via arguments only, for use in BL tags :
    // <xar:module main="false" module="modules" type="admin" func="hooks" curhook="hitcount" return_url="$thisurl" />
    if (empty($return_url)) {
        $return_url = '';
    }
    $data['return_url'] = $return_url;

    if (!empty($curhook)) {
        // Get list of modules likely to be "interested" in hooks
        //$modList = xarModGetList(array('Category' => 'Content'));
        $modList = xarModAPIFunc('modules',
                          'admin',
                          'getlist',
                          array('orderBy'     => 'category/name'));
        //throw back
        if (!isset($modList)) return;

        $oldcat = '';
        for ($i = 0, $max = count($modList); $i < $max; $i++) {
            $modList[$i]['checked'] = '';
            $modList[$i]['links'] = '';

	   	 	$modList[$i]['link'] = xarModURL('modules','admin','modifyorder', array('modulename' => $curhook,
							'modulehookedname' => $modList[$i]['name'] ));

            if ($oldcat != $modList[$i]['category']) {
                $modList[$i]['header'] = xarVarPrepForDisplay($modList[$i]['category']);
                $oldcat = $modList[$i]['category'];
            } else {
                $modList[$i]['header'] = '';
            }
            // Get the list of all item types for this module (if any)
            $itemtypes = xarModAPIFunc($modList[$i]['name'],'user','getitemtypes',
                                       // don't throw an exception if this function doesn't exist
                                       array(), 0);
            if (isset($itemtypes)) {
                $modList[$i]['itemtypes'] = $itemtypes;
            } else {
                $modList[$i]['itemtypes'] = array();
            }
            $modList[$i]['checked'] = array();
            $modList[$i]['links'] = array();
            foreach ($hooklist[$curhook] as $hook => $hookedmods) {
                if (!empty($hookedmods[$modList[$i]['name']])) {
                    foreach ($hookedmods[$modList[$i]['name']] as $itemType => $val) {
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
        $data['curhook'] = $curhook;
        $data['hookedmodules'] = $modList;
        $data['authid'] = xarSecGenAuthKey('modules');

        if (!xarVarFetch('details', 'bool', $details, false, XARVAR_NOT_REQUIRED)) {return;}
        if ($details) {
            $data['DetailsLabel'] = xarML('Hide Details');
            $data['DetailsURL'] = xarModURL('modules','admin','hooks',
                                            array('hook' => $curhook, 'details' => false));

            foreach ($hooklist[$curhook] as $hook => $hookedmods) {
                $data['hooktypes'][] = $hook;
            }
        } else {
            $data['DetailsLabel'] = xarML('Show Details');
            $data['DetailsURL'] = xarModURL('modules','admin','hooks',
                                            array('hook' => $curhook, 'details' => true));
        }
    }

    foreach ($hooklist as $hookmodname => $hooks) {

        // Get module display name
        $regid = xarModGetIDFromName($hookmodname);
        $modinfo = xarModGetInfo($regid);
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
