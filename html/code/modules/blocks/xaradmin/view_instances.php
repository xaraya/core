<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
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
            if (!xarVarFetch('filter', 'pre:trim:str:1:',
                $filter, null, XARVAR_NOT_REQUIRED)) return;
            $list = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array(
                    'filter' => $filter,
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
                        array('block_id' => $block_id, 'tab' => 'config'));
                $item['modify_link'] = array(
                    'label' => xarML('Config'),
                    'title' => xarML('View or modify configuration of this block instance'),
                    'url' => $modify_link,
                );
                // check if this block type supports previews
                $preview_link = (empty($item['type_info']['show_preview'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'tab' => 'preview'));              
                $item['preview_link'] = array(
                    'label' => xarML('Preview'),
                    'title' => xarML('Display preview of this block instance'),
                    'url' => $preview_link,
                );
                // check if this block type supplies help
                $help_link = (empty($item['type_info']['show_help'])) ? '' :
                    xarModURL('blocks', 'admin', 'modify_instance', 
                        array('block_id' => $block_id, 'tab' => 'help'));              
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
            $data['filter'] = $filter;
            
        break;
        case 'bygroup':
            
        break;
        case 'bytype':
            
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
        'compact' => array(
            'url' => xarServer::getCurrentURL(array('tab' => 'compact')),
            'label' => xarML('Compact'),
            'title' => xarML('View a compact list of block instances'),
        ),
    ); 


    return $data;

    // Security
    if (!xarSecurityCheck('EditBlocks', 0, 'Instance')) {return;}

    $data = array();

    if (!xarVarFetch('filter', 'str', $filter, "", XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('startnum', 'int', $startnum,   1,      XARVAR_NOT_REQUIRED)) {return;}

    // Get current style.
    $data['selstyle'] = xarModUserVars::get('blocks', 'selstyle');
    // Set default style if none selected.
    if (empty($data['selstyle'])){
        $data['selstyle'] = 'plain';
    }

    if ($data['selstyle'] == 'bygroup') {
        $order = 'group';
    } elseif ($data['selstyle'] == 'bytype') {
        $order = 'type';
    } else {
        $order = 'name';
    }

    $itemsperpage = xarModVars::get('blocks', 'items_per_page');
    $total = xarMod::apiFunc('blocks', 'user', 'count_instances',
        array('order' => $order, 'filter' => $filter));
    $instances = xarMod::apiFunc('blocks', 'user', 'getall',
        array('filter' => $filter, 'order' => $order, 'startnum' => $startnum, 'numitems' => $itemsperpage));

    $authid = xarSecGenAuthKey();
    // Create extra links and confirmation text.
    foreach ($instances as $index => $instance) {
        $instances[$index]['modifyurl'] = xarModUrl(
            'blocks', 'admin', 'modify_instance',
            array('bid' => $instance['bid'])
        );
        $instances[$index]['deleteurl'] = xarModUrl(
            'blocks', 'admin', 'delete_instance',
            array('bid' => $instance['bid'], 'authid' => $authid)
        );
        $instances[$index]['typeurl'] = xarModUrl(
            'blocks', 'admin', 'view_types',
            array('tid' => $instance['tid'])
        );
        if (isset($instance['groupid'])) {
            $instances[$index]['groupurl'] = xarModUrl(
                'blocks', 'admin', 'modify_instance',
                array('bid' => $instance['groupid'])
            );
        }
        $instances[$index]['deleteconfirm'] = xarML('Delete instance "#(1)"', addslashes($instance['name']));
    }

    $data['authid'] = $authid;
    // State descriptions.
    $data['state_desc'][0] = xarML('Hidden');
    $data['state_desc'][1] = xarML('Inactive');
    $data['state_desc'][2] = xarML('Visible');
    $data['blocks'] = $instances;
    $data['filter'] = $filter;
    $data['itemsperpage'] = $itemsperpage;
    $data['startnum'] = $startnum;
    $data['total'] = $total;

    return $data;
}
?>
