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

    // get types from db 
    $items = xarMod::apiFunc('blocks', 'types', 'getitems');
    
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
                    array('type_id' => $type_id, 'tab' => 'config')),
        );                     
        $item['preview_link'] = array(
            'label' => xarML('Preview'),
            'title' => xarML('View a preview of this block type'),
            'url' => empty($item['type_info']['show_preview']) ? '' :
                xarModURL('blocks', 'admin', 'modify_type', 
                    array('type_id' => $type_id, 'tab' => 'preview')),
        );  
        $item['help_link'] = array(
            'label' => xarML('Help'),
            'title' => xarML('View help information about this block type'),
            'url' => empty($item['type_info']['show_help']) ? '' :
                xarModURL('blocks', 'admin', 'modify_type', 
                    array('type_id' => $type_id, 'tab' => 'help')),
        );     
        $items[$type_id] = $item;
    }
    $data['types'] = $items;    
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    
    return $data;
}
?>