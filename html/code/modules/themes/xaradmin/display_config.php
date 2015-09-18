<?php
/**
 * Display a configuration option
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

function themes_admin_display_config(Array $args=array())
{
    if (!xarVarFetch('itemid' ,    'int',    $data['itemid'] , 0 ,          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObject(array('name' => 'themes_configurations'));

    if (!isset($data['object'])) return;
    if (!$data['object']->checkAccess('display'))
        return xarResponse::Forbidden(xarML('Display #(1) is forbidden', $data['object']->label));

    $data['object']->getItem(array('itemid' => $data['itemid']));
    return $data;
}

?>