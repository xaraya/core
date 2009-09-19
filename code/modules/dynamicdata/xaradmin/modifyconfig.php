<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * This is a standard function to modify the configuration parameters of the
 * module
 * @return array
 */
function dynamicdata_admin_modifyconfig()
{
    $data = xarModAPIFunc('dynamicdata','admin','menu');

    if(!xarSecurityCheck('AdminDynamicData')) return;

    $data['authid'] = xarSecGenAuthKey();

    if (!xarModAPILoad('dynamicdata', 'user')) return;
    $data['fields'] = DataPropertyMaster::getPropertyTypes();
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    // FIXME: This may not work when moving property classes around manually !
    //$data['fieldtypeprop'] =& DataPropertyMaster::getProperty(array('type' => 'fieldtype'));
    sys::import('modules.dynamicdata.xarproperties.fieldtype');

    $descriptor = new DataObjectDescriptor(array('type' => 'fieldtype'));
    $data['fieldtypeprop'] = new FieldTypeProperty($descriptor);

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'name' => xarML('Name'),
                            'label' => xarML('Description'),
                            'informat' => xarML('Input Format'),
                            'outformat' => xarML('Display Format'),
                            'configuration' => xarML('Configuration'),
                        // etc.
                            'new' => xarML('New'),
                      );

    return $data;
}
?>
