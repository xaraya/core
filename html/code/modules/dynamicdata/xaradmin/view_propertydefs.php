<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * This is a standard function to modify the configuration parameters of the
 * module
 * @return array<mixed>|void data for the template display
 */
function dynamicdata_admin_view_propertydefs()
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

    $data['authid'] = xarSec::genAuthKey();

    if (!xarMod::apiLoad('dynamicdata', 'user')) {
        return;
    }
    $data['fields'] = DataPropertyMaster::getPropertyTypes();
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = [];
    }

    // FIXME: This may not work when moving property classes around manually !
    //$data['fieldtypeprop'] =& DataPropertyMaster::getProperty(array('type' => 'fieldtype'));
    sys::import('modules.dynamicdata.xarproperties.fieldtype');

    $descriptor = new DataObjectDescriptor(['type' => 'fieldtype']);
    $data['fieldtypeprop'] = new FieldTypeProperty($descriptor);

    $data['labels'] = [
        'id' => xarML('ID'),
        'name' => xarML('Name'),
        'label' => xarML('Description'),
        'informat' => xarML('Input Format'),
        'outformat' => xarML('Display Format'),
        'configuration' => xarML('Configuration'),
        // etc.
        'new' => xarML('New'),
    ];

    return $data;
}
