<?php
/**
 * Utility function to pass individual menu items
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/13.html
 * @subpackage Blocks
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Jim McDonald, Paul Rosania
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function blocks_adminapi_getmenulinks()
{
    $menulinks = array();
    if (xarSecurityCheck('EditBlock', 0)) {
      $menulinks[] = Array('url'   => xarModURL('blocks',
                                                   'admin',
                                                   'overview'),
                              'title' => xarML('Blocks Overview'),
                              'label' => xarML('Overview'));
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'view_instances'),
            'title' => xarML('View or edit all block instances'),
            'label' => xarML('View Instances')
        );
    }
    if (xarSecurityCheck('AddBlock', 0)) {
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'new_instance'),
            'title' => xarML('Add a new block instance'),
            'label' => xarML('Add Instance')
        );
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'view_groups'),
            'title' => xarML('View the defined block groups'),
            'label' => xarML('View Groups')
        );
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'new_group'),
            'title' => xarML('Add a new group of blocks'),
            'label' => xarML('Add Group')
        );
    }
    if (xarSecurityCheck('AdminBlock', 0)) {
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'view_types'),
            'title' => xarML('View block types'),
            'label' => xarML('View Block Types')
        );
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'new_type'),
            'title' => xarML('Add a new block type into the system'),
            'label' => xarML('Add Block Type')
        );
    }
    return $menulinks;
}

?>
