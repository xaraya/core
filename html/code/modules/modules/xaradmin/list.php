<?php
/**
 * List modules and current settings
 *
 * @package modules
 * @subpackage modules module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * List modules and current settings
 * @author Xaraya Development Team
 * @param several params from the associated form in template
 * @todo  finish cleanup, styles, filters and sort orders
 * @return array data for the template display
 */
function modules_admin_list()
{
    // Security
    if(!xarSecurityCheck('AdminModules')) return;

    if(!xarMod::apiFunc('modules', 'admin', 'regenerate')) return;

    $coremods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules');

    // display phase     
    $data = array();
        
    if (!xarVarFetch('startnum', 'int:1:',
        $data['startnum'], 1, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('state', 'int',
        $data['state'], null, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('modtype', 'int:0:2', // 0=all, 1=core only, 2=non-core only
        $data['modtype'], null, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('sort', 'pre:trim:upper:enum:ASC:DESC',
        $data['sort'], 'ASC', XARVAR_NOT_REQUIRED)) return;
    
    if (!isset($data['state']))
        $data['state'] = xarModUserVars::get('modules', 'selfilter');
    if (!isset($data['state']))
        $data['state'] = XARMOD_STATE_ANY;
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

    $authid = xarSecGenAuthKey();
    
    foreach ($items as $key => $item) {
        $item['iscore'] = in_array($item['name'], $coremods);
        $item['info_url'] = xarModURL('modules', 'admin', 'modinfonew', 
            array('id' => $item['regid']));
        switch ($item['state']) {
            case XARMOD_STATE_UNINITIALISED: // 1
                $item['init_url'] = xarModURL('modules', 'admin', 'install',
                    array('id' => $item['regid'], 'authid' => $authid)); 
                break;
            case XARMOD_STATE_INACTIVE:  // 2
                $item['activate_url'] = xarModURL('modules', 'admin', 'install',
                    array('id' => $item['regid'], 'authid' => $authid));             
                $item['remove_url'] = xarModURL('modules', 'admin', 'remove',
                    array('id' => $item['regid'], 'authid' => $authid));  
                break;
            case XARMOD_STATE_ACTIVE:  // 3
                if (!$item['iscore']) {
                    $item['deactivate_url'] = xarModURL('modules', 'admin', 'deactivate',
                        array('id' => $item['regid'], 'authid' => $authid));                     
                }
                if (!empty($item['admin_capable']))
                    $item['admin_url'] = xarModURL($item['name'], 'admin');
                $item['hooks_url'] = xarModURL('modules', 'admin', 'modify',
                    array('id' => $item['regid']));
                break;
            case XARMOD_STATE_UPGRADED: // 5
                $item['upgrade_url'] = xarModURL('modules', 'admin', 'upgrade',
                    array('id' => $item['regid'], 'authid' => $authid));              
                break;
            case XARMOD_STATE_MISSING_FROM_UNINITIALISED: // 4           
            case XARMOD_STATE_MISSING_FROM_INACTIVE: // 7
            case XARMOD_STATE_MISSING_FROM_ACTIVE: // 8
            case XARMOD_STATE_MISSING_FROM_UPGRADED: // 9
                $item['remove_url'] = xarModURL('modules', 'admin', 'remove',
                    array('id' => $item['regid'], 'authid' => $authid));             
                break;
            case XARMOD_STATE_ERROR_UNINITIALISED: // 10
            case XARMOD_STATE_ERROR_INACTIVE: // 11
            case XARMOD_STATE_ERROR_ACTIVE: // 12
            case XARMOD_STATE_ERROR_UPGRADED: // 13
                $item['error_url'] = xarModURL('modules', 'admin', 'viewerror',
                    array('id' => $item['regid'], 'authid' => $authid));              
                break;
            default:
                $item['remove_url'] = xarModURL('modules', 'admin', 'remove',
                    array('id' => $item['regid'], 'authid' => $authid));              
                break;
        }
        
        $items[$key] = $item;
    }
        
    $data['items'] = $items;

    $data['states'] = array(
        XARMOD_STATE_ANY => 
            array('id' => XARMOD_STATE_ANY, 'name' => xarML('All')),
        XARMOD_STATE_INSTALLED =>
            array('id' => XARMOD_STATE_INSTALLED, 'name' => xarML('Installed')),
        XARMOD_STATE_ACTIVE =>    
            array('id' => XARMOD_STATE_ACTIVE, 'name' => xarML('Active')),
        XARMOD_STATE_UPGRADED =>    
            array('id' => XARMOD_STATE_UPGRADED, 'name' => xarML('Upgraded')),
        XARMOD_STATE_INACTIVE =>    
            array('id' => XARMOD_STATE_INACTIVE, 'name' => xarML('Inactive')),
        XARMOD_STATE_UNINITIALISED =>
            array('id' => XARMOD_STATE_UNINITIALISED, 'name' => xarML('Not Installed')),
        XARMOD_STATE_MISSING_FROM_ACTIVE =>   
            array('id' => XARMOD_STATE_MISSING_FROM_ACTIVE, 'name' => xarML('Missing (Active)')),
        XARMOD_STATE_MISSING_FROM_UPGRADED =>
            array('id' => XARMOD_STATE_MISSING_FROM_UPGRADED, 'name' => xarML('Missing (Upgraded)')),
        XARMOD_STATE_MISSING_FROM_INACTIVE => 
            array('id' => XARMOD_STATE_MISSING_FROM_INACTIVE, 'name' => xarML('Missing (Inactive)')),
        XARMOD_STATE_MISSING_FROM_UNINITIALISED =>
            array('id' => XARMOD_STATE_MISSING_FROM_UNINITIALISED, 'name' => xarML('Missing (Not Installed)')),
        XARMOD_STATE_ERROR_ACTIVE =>    
            array('id' => XARMOD_STATE_ERROR_ACTIVE, 'name' => xarML('Error (Active)')),
        XARMOD_STATE_ERROR_UPGRADED =>    
            array('id' => XARMOD_STATE_ERROR_UPGRADED, 'name' => xarML('Error (Upgraded)')),
        XARMOD_STATE_ERROR_INACTIVE =>    
            array('id' => XARMOD_STATE_ERROR_INACTIVE, 'name' => xarML('Error (Inactive)')),
        XARMOD_STATE_ERROR_UNINITIALISED =>
            array('id' => XARMOD_STATE_ERROR_UNINITIALISED, 'name' => xarML('Error (Not Installed)')),
    );

    $data['modtypes'] = array(
        0 => array('id' => 0, 'name' => xarML('All')),
        1 => array('id' => 1, 'name' => xarML('Core')),
        2 => array('id' => 2, 'name' => xarML('Non-core')),
    );

    // remember filter selections for current user 
    xarModUserVars::set('modules', 'selfilter', $data['state']);
    xarModUserVars::set('modules', 'hidecore', $data['modtype']);

    $count = count($items);
    if ($data['state'] == XARMOD_STATE_ANY) {
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