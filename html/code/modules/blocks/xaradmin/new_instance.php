<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * display form for a new block instance
 * @author Jim McDonald
 * @author Paul Rosania
 * @return array data for the template display
*/
function blocks_admin_new_instance()
{
    // Security
    if (!xarSecurityCheck('AddBlocks', 0, 'Instance')) {return;}

    // Can specify block types for a single module.
    xarVarFetch('formodule', 'str:1', $module, NULL, XARVAR_NOT_REQUIRED);

    // Fetch block type list.
    $types = xarMod::apiFunc(
        'blocks', 'user', 'getallblocktypes',
        array('order' => 'module,type', 'module' => $module)
    );

    $block_types = array();
    foreach ($types as $type) {
        if (!empty($type['info']['new_access'])) {
            // Decide whether the current user can create blocks of this type
            $args = array(
                'component' => 'Block',
                'instance' => $type['tid'] . ":All:All",
                'group' => $type['info']['new_access']['group'],
                'level' => $type['info']['new_access']['level'],
            );
            $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
            if (!$accessproperty->check($args)) continue;
        }
        $block_types[$type['tid']] = $type;
    }

    $block_groups = xarMod::apiFunc('blocks', 'user', 'getall', array('type' => 'blockgroup'));

    $data = array();
    $data['block_types'] = $block_types;
    $data['block_groups'] = $block_groups;
    $data['create_label'] = xarML('Create Instance');
    // populate block state options
    $data['state_options'] = array(
        array('id' => xarBlock::BLOCK_STATE_INACTIVE, 'name' => xarML('Inactive')),
        array('id' => xarBlock::BLOCK_STATE_HIDDEN, 'name' => xarML('Hidden')),
        array('id' => xarBlock::BLOCK_STATE_VISIBLE, 'name' => xarML('Visible')),
    );

    return $data;

}
?>
