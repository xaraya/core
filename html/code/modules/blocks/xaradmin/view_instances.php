<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * view block instances
 * @author Jim McDonald
 * @author Paul Rosania
 * @return array data for the template display
 */
function blocks_admin_view_instances()
{
    if (!xarSecurityCheck('ManageBlocks')) return;
    
    $data = array();
    
    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:',
        $data['tab'], 'list', XARVAR_NOT_REQUIRED)) return;

    $access_property = DataPropertyMaster::getProperty(array('name' => 'access'));
        
    switch ($data['tab']) {
        case 'list':
            if (!xarVarFetch('startnum', 'int:1',
                $data['startnum'], 1, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('filter', 'pre:trim:str:1:',
                $data['filter'], null, XARVAR_NOT_REQUIRED)) return;
            $data['items_per_page'] = xarModVars::get('blocks', 'items_per_page');

            $data['total'] = xarMod::apiFunc('blocks', 'instances', 'countitems',
                array(
                    'filter' => $data['filter'],
                ));
            $list = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array(
                    'filter' => $data['filter'],
                    'startnum' => $data['startnum'],
                    'numitems' => $data['items_per_page'],
                ));
            
            foreach ($list as $block_id => $item) {
                // get any groups this instance belongs to
                if (!empty($item['content']['instance_groups'])) {
                    $item['groups'] = xarMod::apiFunc('blocks', 'instances', 'getitems',
                        array(
                            'block_id' => array_keys($item['content']['instance_groups']),
                            'type_category' => 'group',
                        ));
                }
                // all managers can view info about instances 
                $item['info_link'] = array(
                    'label' => xarML('Info'),
                    'title' => xarML('View detail information about this block instance'),
                    'url' => xarModURL('blocks', 'admin', 'modify_instance', array('block_id' => $block_id)),
                );
                // all managers can view info about types
                $item['type_link'] = array(
                    'label' => xarML('Type Info'),
                    'title' => xarML('View detail information about this block type'),
                    'url' => xarModURL('blocks', 'admin', 'modify_type', array('type_id' => $item['type_id'])),
                );
                // check modify access        
                $args = array(
                    'module' => $item['module'],
                    'component' => 'Block',
                    'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                    'group' => $item['content']['modify_access']['group'],
                    'level' => $item['content']['modify_access']['level'],
                );
                $modify_link = (!$access_property->check($args)) ?  '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'interface' => 'config'));
                $item['modify_link'] = array(
                    'label' => xarML('Config'),
                    'title' => xarML('View or modify configuration of this block instance'),
                    'url' => $modify_link,
                );
                // check if this block type supports previews
                $preview_link = (empty($item['type_info']['show_preview'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'interface' => 'display', 'block_method' => 'preview'));
                $item['preview_link'] = array(
                    'label' => xarML('Preview'),
                    'title' => xarML('Display preview of this block instance'),
                    'url' => $preview_link,
                );
                // check if this block type supplies help
                $help_link = (empty($item['type_info']['show_help'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'interface' => 'display', 'block_method' => 'help'));
                $item['help_link'] = array(
                    'label' => xarML('Help'),
                    'title' => xarML('Display help information about this block type'),
                    'url' => $help_link,
                );
                // check delete access        
                $args = array(
                    'module' => $item['module'],
                    'component' => 'Block',
                    'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                    'group' => $item['content']['delete_access']['group'],
                    'level' => $item['content']['delete_access']['level'],
                );
                $delete_link = (!$access_property->check($args)) ?  '' :
                    xarModURL('blocks', 'admin', 'delete_instance', 
                        array('block_id' => $block_id));
                $item['delete_link'] = array(
                    'label' => xarML('Delete'),
                    'title' => xarML('Delete this block instance'),
                    'url' => $delete_link,
                );

                $list[$block_id] = $item;
            }
            
            $data['list'] = $list;
            
        break;
        case 'bygroup':
            $groups = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array(
                    'type_category' => 'group',
                ));
            $blocks = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array(
                    'type_category' => 'block',
                ));
            $list = array();
            foreach ($groups as $group_id => $group) {

                // all managers can view info about instances 
                $group['info_link'] = array(
                    'label' => xarML('Info'),
                    'title' => xarML('View detail information about this block instance'),
                    'url' => xarModURL('blocks', 'admin', 'modify_instance', array('block_id' => $group_id)),
                );
                // all managers can view info about types
                $group['type_link'] = array(
                    'label' => xarML('Type Info'),
                    'title' => xarML('View detail information about this block type'),
                    'url' => xarModURL('blocks', 'admin', 'modify_type', array('type_id' => $group['type_id'])),
                );
                // check modify access        
                $args = array(
                    'module' => $group['module'],
                    'component' => 'Block',
                    'instance' => $group['type'] . ":" . $group['name'] . ":" . $group['block_id'],
                    'group' => $group['content']['modify_access']['group'],
                    'level' => $group['content']['modify_access']['level'],
                );
                $modify_link = (!$access_property->check($args)) ?  '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $group_id, 'interface' => 'config'));
                $group['modify_link'] = array(
                    'label' => xarML('Config'),
                    'title' => xarML('View or modify configuration of this block instance'),
                    'url' => $modify_link,
                );
                // check if this block type supports previews
                $preview_link = (empty($group['type_info']['show_preview'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $group_id, 'interface' => 'display', 'block_method' => 'preview'));
                $group['preview_link'] = array(
                    'label' => xarML('Preview'),
                    'title' => xarML('Display preview of this block instance'),
                    'url' => $preview_link,
                );
                // check if this block type supplies help
                $help_link = (empty($group['type_info']['show_help'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $group_id, 'interface' => 'display', 'block_method' => 'help'));
                $group['help_link'] = array(
                    'label' => xarML('Help'),
                    'title' => xarML('Display help information about this block type'),
                    'url' => $help_link,
                );
                // check delete access        
                $args = array(
                    'module' => $group['module'],
                    'component' => 'Block',
                    'instance' => $group['type'] . ":" . $group['name'] . ":" . $group['block_id'],
                    'group' => $group['content']['delete_access']['group'],
                    'level' => $group['content']['delete_access']['level'],
                );
                $delete_link = (!$access_property->check($args)) ?  '' :
                    xarModURL('blocks', 'admin', 'delete_instance', 
                        array('block_id' => $group_id));
                $group['delete_link'] = array(
                    'label' => xarML('Delete'),
                    'title' => xarML('Delete this block instance'),
                    'url' => $delete_link,
                );

                if (!empty($group['content']['group_instances'])) {
                    foreach ($group['content']['group_instances'] as $block_id) {
                        if (!isset($blocks[$block_id])) continue;
                        $block = $blocks[$block_id];
                        // all managers can view info about instances 
                        $block['info_link'] = array(
                            'label' => xarML('Info'),
                            'title' => xarML('View detail information about this block instance'),
                            'url' => xarModURL('blocks', 'admin', 'modify_instance', array('block_id' => $block_id)),
                        );
                        // all managers can view info about types
                        $block['type_link'] = array(
                            'label' => xarML('Type Info'),
                            'title' => xarML('View detail information about this block type'),
                            'url' => xarModURL('blocks', 'admin', 'modify_type', array('type_id' => $block['type_id'])),
                        );
                        // check modify access        
                        $args = array(
                            'module' => $block['module'],
                            'component' => 'Block',
                            'instance' => $block['type'] . ":" . $block['name'] . ":" . $block['block_id'],
                            'group' => $block['content']['modify_access']['group'],
                            'level' => $block['content']['modify_access']['level'],
                        );
                        $modify_link = (!$access_property->check($args)) ?  '' :
                            xarModURL('blocks', 'admin', 'modify_instance', 
                                array('block_id' => $block_id, 'interface' => 'config'));
                        $block['modify_link'] = array(
                            'label' => xarML('Config'),
                            'title' => xarML('View or modify configuration of this block instance'),
                            'url' => $modify_link,
                        );
                        // check if this block type supports previews
                        $preview_link = (empty($block['type_info']['show_preview'])) ? '' :
                            xarModURL('blocks', 'admin', 'modify_instance', 
                                array('block_id' => $block_id, 'interface' => 'display', 'block_method' => 'preview'));              
                        $block['preview_link'] = array(
                            'label' => xarML('Preview'),
                            'title' => xarML('Display preview of this block instance'),
                            'url' => $preview_link,
                        );
                        // check if this block type supplies help
                        $help_link = (empty($block['type_info']['show_help'])) ? '' :
                            xarModURL('blocks', 'admin', 'modify_instance', 
                                array('block_id' => $block_id, 'interface' => 'display', 'block_method' => 'help'));              
                        $block['help_link'] = array(
                            'label' => xarML('Help'),
                            'title' => xarML('Display help information about this block type'),
                            'url' => $help_link,
                        );
                        // check delete access        
                        $args = array(
                            'module' => $block['module'],
                            'component' => 'Block',
                            'instance' => $block['type'] . ":" . $block['name'] . ":" . $block['block_id'],
                            'group' => $block['content']['delete_access']['group'],
                            'level' => $block['content']['delete_access']['level'],
                        );
                        $delete_link = (!$access_property->check($args)) ?  '' :
                            xarModURL('blocks', 'admin', 'delete_instance', 
                                array('block_id' => $block_id));
                        $block['delete_link'] = array(
                            'label' => xarML('Delete'),
                            'title' => xarML('Delete this block instance'),
                            'url' => $delete_link,
                        );
                        $group['instances'][$block_id] = $block;                       
                    }
                }
                $list[$group_id] = $group;
            }
            $data['list'] = $list;           
            
        break;
        case 'bytype':
            $types = xarMod::apiFunc('blocks', 'types', 'getitems');
            foreach ($types as $type_id => $item) {
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
                            array('type_id' => $type_id,  'interface' => 'display', 'block_method' => 'preview')),
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
                $types[$type_id] = $item;            
            }        
            $instances = xarMod::apiFunc('blocks', 'instances', 'getitems');
            foreach ($instances as $block_id => $item) {
                // get any groups this instance belongs to
                if (!empty($item['content']['instance_groups'])) {
                    foreach (array_keys($item['content']['instance_groups']) as $group_id) {
                        if (!isset($instances[$group_id])) continue;
                        $item['groups'][$group_id] = $instances[$group_id];
                    }
                }
                // all managers can view info about instances 
                $item['info_link'] = array(
                    'label' => xarML('Info'),
                    'title' => xarML('View detail information about this block instance'),
                    'url' => xarModURL('blocks', 'admin', 'modify_instance', array('block_id' => $block_id)),
                );
                // all managers can view info about types
                $item['type_link'] = array(
                    'label' => xarML('Type Info'),
                    'title' => xarML('View detail information about this block type'),
                    'url' => xarModURL('blocks', 'admin', 'modify_type', array('type_id' => $item['type_id'])),
                );
                // check modify access        
                $args = array(
                    'module' => $item['module'],
                    'component' => 'Block',
                    'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                    'group' => $item['content']['modify_access']['group'],
                    'level' => $item['content']['modify_access']['level'],
                );
                $modify_link = (!$access_property->check($args)) ?  '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'interface' => 'config'));
                $item['modify_link'] = array(
                    'label' => xarML('Config'),
                    'title' => xarML('View or modify configuration of this block instance'),
                    'url' => $modify_link,
                );
                // check if this block type supports previews
                $preview_link = (empty($item['type_info']['show_preview'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'interface' => 'display', 'block_method' => 'preview'));
                $item['preview_link'] = array(
                    'label' => xarML('Preview'),
                    'title' => xarML('Display preview of this block instance'),
                    'url' => $preview_link,
                );
                // check if this block type supplies help
                $help_link = (empty($item['type_info']['show_help'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'interface' => 'display', 'block_method' => 'help'));
                $item['help_link'] = array(
                    'label' => xarML('Help'),
                    'title' => xarML('Display help information about this block type'),
                    'url' => $help_link,
                );
                // check delete access        
                $args = array(
                    'module' => $item['module'],
                    'component' => 'Block',
                    'instance' => $item['type'] . ":" . $item['name'] . ":" . $item['block_id'],
                    'group' => $item['content']['delete_access']['group'],
                    'level' => $item['content']['delete_access']['level'],
                );
                $delete_link = (!$access_property->check($args)) ?  '' :
                    xarModURL('blocks', 'admin', 'delete_instance', 
                        array('block_id' => $block_id));
                $item['delete_link'] = array(
                    'label' => xarML('Delete'),
                    'title' => xarML('Delete this block instance'),
                    'url' => $delete_link,
                );
                $types[$item['type_id']]['instances'][$block_id] = $item;
            }
            
            $data['list'] = $types;
                
        break;
        case 'compact':
            //ugh!
        break;
    }
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    $data['instance_states'] = xarMod::apiFunc('blocks', 'instances', 'getstates');
    $data['blocktabs'] = array(
        'list' => array(
            'url' => xarServer::getCurrentURL(array('tab' => 'list')),
            'label' => xarML('List'),
            'title' => xarML('View list of block instances'),
        ),
        'bygroup' => array(
            'url' => xarServer::getCurrentURL(array('tab' => 'bygroup')),
            'label' => xarML('By Group'),
            'title' => xarML('View list of block instances grouped by block group'),
        ),
        'bytype' => array(
            'url' => xarServer::getCurrentURL(array('tab' => 'bytype')),
            'label' => xarML('By Type'),
            'title' => xarML('View list of block instances grouped by block type'),
        ),   
        /* drop this, we can revisit if anyone complains   
        'compact' => array(
            'url' => xarServer::getCurrentURL(array('tab' => 'compact')),
            'label' => xarML('Compact'),
            'title' => xarML('View a compact list of block instances'),
        ),
        */
    ); 


    return $data;

}
?>