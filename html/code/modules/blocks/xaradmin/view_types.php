<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * view block types
 * @author Jim McDonald
 * @author Paul Rosania
 * @return array data for the template display
 */
function blocks_admin_view_types()
{
    // Security - checkme: Edit vs Manage? 
    if (!xarSecurityCheck('ManageBlocks')) {return;}

    // refresh block types
    if (!xarMod::apiFunc('blocks', 'types', 'refresh')) return;
    
    $data = array();
    if (!xarVarFetch('startnum', 'int:1',
        $data['startnum'], 1, XARVAR_NOT_REQUIRED)) return;
    $data['items_per_page'] = xarModVars::get('blocks', 'items_per_page');
    // get types from db 
    $items = xarMod::apiFunc('blocks', 'types', 'getitems',
        array(
            'startnum' => $data['startnum'],
            'numitems' => $data['items_per_page'],
        ));
    $data['total'] = xarMod::apiFunc('blocks', 'types', 'countitems');    

    $access_property = DataPropertyMaster::getProperty(array('name' => 'access'));
    
    foreach ($items as $type_id => $item) {
        $item['info_link'] = array(
            'label' => xarML('Info'),
            'title' => xarML('View detail information about this block type'),
            'url' => xarModURL('blocks', 'admin', 'modify_type', 
                array('type_id' => $type_id)),
        );
        $item['modify_link'] = array(
            'label' => xarML('Config'),
            'title' => xarML('View or modify default configuration for this block type'),
            'url' => !xarSecurityCheck('AdminBlocks', 0) ? '' :
                xarModURL('blocks', 'admin', 'modify_type', 
                    array('type_id' => $type_id, 'interface' => 'config')),
        );                     
        $item['preview_link'] = array(
            'label' => xarML('Preview'),
            'title' => xarML('View a preview of this block type'),
            'url' => empty($item['type_info']['show_preview']) ? '' :
                xarModURL('blocks', 'admin', 'modify_type', 
                    array('type_id' => $type_id, 'interface' => 'display', 'block_method' => 'preview')),
        );  
        $item['help_link'] = array(
            'label' => xarML('Help'),
            'title' => xarML('View help information about this block type'),
            'url' => empty($item['type_info']['show_help']) ? '' :
                xarModURL('blocks', 'admin', 'modify_type', 
                    array('type_id' => $type_id, 'interface' => 'display', 'block_method' => 'help')),
        );
        // check new instance access        
        $access = array(
            'module' => $item['module'],
            'component' => 'Block',
            'instance' => $item['type'] . ":All:All",
            'group' => $item['type_info']['add_access']['group'],
            'level' => $item['type_info']['add_access']['level'],
        );
        $item['add_link'] = array(
            'label' => xarML('Add'),
            'title' => xarML('Create a new instance of this block type'),
            'url' => (!$access_property->check($access) || $item['type_state'] != xarBlock::TYPE_STATE_ACTIVE) ?  '' :
                xarModURL('blocks', 'admin', 'new_instance',
                    array('type_id' => $type_id, 'phase' => 'form')),
        );
        $items[$type_id] = $item;
    }
    $data['types'] = $items;    
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    
    return $data;
}
?>