<?php
/**
 * View the configuration options
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/70.html
 */

sys::import('modules.dynamicdata.class.objects.master');

function themes_admin_view_configs()
{
    // Security
    if(!xarSecurityCheck('EditThemes')) return;

    $data['object'] = DataObjectMaster::getObjectList(array('name' => 'themes_configurations'));

    if (!isset($data['object'])) {return;}
    if (!$data['object']->checkAccess('view'))
        return xarResponse::Forbidden(xarML('View #(1) is forbidden', $data['object']->label));

    // Count the number of items matching the preset arguments - do this before getItems()
    $data['object']->countItems();

    // Get the selected items using the preset arguments
    $data['object']->getItems();

    return $data;
}

?>