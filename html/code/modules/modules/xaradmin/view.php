<?php
/**
 * List modules and current settings
 *
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * List modules and current settings
 * @author Xaraya Development Team
 * @param several params from the associated form in template
 * @todo  finish cleanup, styles, filters and sort orders
 * @return array data for the template display
 */
function modules_admin_view()
{
    // Security
    if(!xarSecurity::check('AdminModules')) return;

    if(!xarMod::apiFunc('modules', 'admin', 'regenerate')) return;

    $coremods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories');

    // Display phase     
    $data = array();
       
    // Get the place at which we want to start disolaying
    if (!xarVar::fetch('startnum', 'int:1:', $data['startnum'], 1, xarVar::NOT_REQUIRED)) return;
    // Check for a state filter
    if (!xarVar::fetch('state', 'int', $data['state'], null, xarVar::DONT_SET)) return;
	// Check for a module type filter
	// 0=all, 1=core only, 2=non-core only
    if (!xarVar::fetch('modtype', 'int:0:2', $data['modtype'], null, xarVar::DONT_SET)) return;
    // Check for a sort: we can sort by name ASC or DESC
    if (!xarVar::fetch('sort', 'pre:trim:upper:enum:ASC:DESC', $data['sort'], 'ASC', xarVar::NOT_REQUIRED)) return;
    
    // Save the filters of this user
    if (!isset($data['state']))
        $data['state'] = xarModUserVars::get('modules', 'selfilter');
    if (!isset($data['state']))
        $data['state'] = xarMod::STATE_ANY;
    if (!isset($data['modtype']))
        $data['modtype'] = xarModUserVars::get('modules', 'hidecore');   
    if (!isset($data['modtype']))
        $data['modtype'] = 0;
    $data['items_per_page'] = xarModVars::get('modules', 'items_per_page');
    $data['useicons'] = xarModVars::get('modules', 'use_module_icons');
        
    $itemargs = array(
        'state' => $data['state'],
    );
    
    if ($data['modtype'] == 1) {
        // core only
        $itemargs['name'] = $coremods;
    } elseif ($data['modtype'] == 2) {
        // non-core only 
        $itemargs['include_core'] = false;
    }

    $data['total'] = xarMod::apiFunc('modules', 'admin', 'countitems', $itemargs);

    $itemargs += array(
        'startnum' => $data['startnum'],
        'numitems' => $data['items_per_page'],
        'sort' => 'name '.$data['sort'],
    );

    $items = xarMod::apiFunc('modules', 'admin', 'getitems', $itemargs);

    $authid = xarSec::genAuthKey();

    foreach ($items as $key => $item) {
        $item['iscore'] = in_array($item['name'], $coremods);
        $item['info_url'] = xarController::URL('modules', 'admin', 'modinfo', 
            array('id' => $item['regid']));
        $return_url = xarServer::getCurrentURL(array('state' => $data['state'] != 0 ? 0 : null), false, $item['name']);
        $return_url = urlencode($return_url);
        switch ($item['state']) {
            case xarMod::STATE_UNINITIALISED: // 1
                $item['init_url'] = xarController::URL('modules', 'admin', 'install',
                    array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url)); 
                break;
            case xarMod::STATE_INACTIVE:  // 2
                $item['activate_url'] = xarController::URL('modules', 'admin', 'install',
                    array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url));
                $item['remove_url'] = xarController::URL('modules', 'admin', 'remove',
                    array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url));  
                break;
            case xarMod::STATE_ACTIVE:  // 3
                if (!$item['iscore']) {
                    $item['deactivate_url'] = xarController::URL('modules', 'admin', 'deactivate',
                        array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url));
                }
                if (!empty($item['admin_capable']))
                    $item['admin_url'] = xarController::URL($item['name'], 'admin');
                $item['hooks_url'] = xarController::URL('modules', 'admin', 'modify',
                    array('id' => $item['regid']));
                break;
            case xarMod::STATE_UPGRADED: // 5
                $item['upgrade_url'] = xarController::URL('modules', 'admin', 'upgrade',
                    array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url));
                break;
            case xarMod::STATE_MISSING_FROM_UNINITIALISED: // 4           
            case xarMod::STATE_MISSING_FROM_INACTIVE: // 7
            case xarMod::STATE_MISSING_FROM_ACTIVE: // 8
            case xarMod::STATE_MISSING_FROM_UPGRADED: // 9
                $item['remove_url'] = xarController::URL('modules', 'admin', 'remove',
                    array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url));
                break;
            case xarMod::STATE_ERROR_UNINITIALISED: // 10
            case xarMod::STATE_ERROR_INACTIVE: // 11
            case xarMod::STATE_ERROR_ACTIVE: // 12
            case xarMod::STATE_ERROR_UPGRADED: // 13
                $item['error_url'] = xarController::URL('modules', 'admin', 'viewerror',
                    array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url));
                break;
            default:
                $item['remove_url'] = xarController::URL('modules', 'admin', 'remove',
                    array('id' => $item['regid'], 'authid' => $authid, 'return_url' => $return_url));
                break;
        }
        
        $items[$key] = $item;
    }
        
    $data['items'] = $items;

    $data['states'] = array(
        xarMod::STATE_ANY => 
            array('id' => xarMod::STATE_ANY, 'name' => xarML('All')),
        xarMod::STATE_INSTALLED =>
            array('id' => xarMod::STATE_INSTALLED, 'name' => xarML('Installed')),
        xarMod::STATE_ACTIVE =>    
            array('id' => xarMod::STATE_ACTIVE, 'name' => xarML('Active')),
        xarMod::STATE_UPGRADED =>    
            array('id' => xarMod::STATE_UPGRADED, 'name' => xarML('Upgraded')),
        xarMod::STATE_INACTIVE =>    
            array('id' => xarMod::STATE_INACTIVE, 'name' => xarML('Inactive')),
        xarMod::STATE_UNINITIALISED =>
            array('id' => xarMod::STATE_UNINITIALISED, 'name' => xarML('Not Installed')),
        xarMod::STATE_MISSING_FROM_ACTIVE =>   
            array('id' => xarMod::STATE_MISSING_FROM_ACTIVE, 'name' => xarML('Missing (Active)')),
        xarMod::STATE_MISSING_FROM_UPGRADED =>
            array('id' => xarMod::STATE_MISSING_FROM_UPGRADED, 'name' => xarML('Missing (Upgraded)')),
        xarMod::STATE_MISSING_FROM_INACTIVE => 
            array('id' => xarMod::STATE_MISSING_FROM_INACTIVE, 'name' => xarML('Missing (Inactive)')),
        xarMod::STATE_MISSING_FROM_UNINITIALISED =>
            array('id' => xarMod::STATE_MISSING_FROM_UNINITIALISED, 'name' => xarML('Missing (Not Installed)')),
        xarMod::STATE_ERROR_ACTIVE =>    
            array('id' => xarMod::STATE_ERROR_ACTIVE, 'name' => xarML('Error (Active)')),
        xarMod::STATE_ERROR_UPGRADED =>    
            array('id' => xarMod::STATE_ERROR_UPGRADED, 'name' => xarML('Error (Upgraded)')),
        xarMod::STATE_ERROR_INACTIVE =>    
            array('id' => xarMod::STATE_ERROR_INACTIVE, 'name' => xarML('Error (Inactive)')),
        xarMod::STATE_ERROR_UNINITIALISED =>
            array('id' => xarMod::STATE_ERROR_UNINITIALISED, 'name' => xarML('Error (Not Installed)')),
    );

    $data['modtypes'] = array(
        0 => array('id' => 0, 'name' => xarML('All')),
        1 => array('id' => 1, 'name' => xarML('Core')),
        2 => array('id' => 2, 'name' => xarML('Non-core')),
    );

    // Remember filter selections for current user 
    xarModUserVars::set('modules', 'selfilter', $data['state']);
    xarModUserVars::set('modules', 'hidecore', $data['modtype']);

    $count = count($items);
    if ($data['state'] == xarMod::STATE_ANY) {
        if ($data['modtype'] == 0) {
            $searched = xarML('Showing #(1) modules', $count);
        } else {
            $searched = xarML('Showing #(1) #(2) modules', $count, $data['modtypes'][$data['modtype']]['name']);
        }
    } else {
        if ($data['modtype'] == 0) {
            $searched = xarML('Showing #(1) modules in #(2) state', $count, $data['states'][$data['state']]['name']);
        } else {
            $searched = xarML('Showing #(1) #(2) modules in #(3) state', $count, $data['modtypes'][$data['modtype']]['name'], $data['states'][$data['state']]['name']);
        }    
    }
    $data['searched'] = $searched;

    return $data;    
}
?>