<?php
/**
 * Modify configuration for a module
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * modify configuration for a module - hook for ('module','modifyconfig','GUI')
 *
 * @param int $args['objectid'] ID of the object
 * @param array $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_modifyconfighook($args)
{
    extract($args);

    if (!isset($extrainfo)) throw new EmptyParameterException('extrainfo');

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    // don't allow hooking to yourself in DD
    if ($modname == 'dynamicdata') {
        return '';
    }

    $module_id = xarMod::getRegID($modname);
    if (empty($module_id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name', 'admin', 'modifyconfighook', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return;

    $args = DataObjectDescriptor::getObjectID(array('module_id'  => $module_id,
                                       'itemtype'  => $itemtype));
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('objectid' => $args['objectid']));
    if (!isset($fields) || $fields == false) {
        $fields = array();
    }

    $labels = array(
                    'id' => xarML('ID'),
                    'name' => xarML('Name'),
                    'label' => xarML('Label'),
                    'type' => xarML('Field Format'),
                    'defaultvalue' => xarML('Default'),
                    'source' => xarML('Data Source'),
                    'configuration' => xarML('Configuration'),
                   );

    $labels['dynamicdata'] = xarML('Dynamic Data Fields');
    $labels['config'] = xarML('modify');

    $data = array();
    $data['labels'] = $labels;
    $data['link'] = xarModURL('dynamicdata','admin','modifyprop',
                              array('module_id' => $module_id,
                                    'itemtype' => $itemtype));
    $data['fields'] = $fields;
    $data['fieldtypeprop'] = & DataPropertyMaster::getProperty(array('type' => 'fieldtype'));

    $object = & DataObjectMaster::getObject(array('moduleid' => $module_id,
                                                  'itemtype' => $itemtype,
                                                  'extend' => false));
    if (!empty($object)) {
        if (!empty($object->template)) {
            $template = $object->template;
        } else {
            $template = $object->name;
        }
    } else {
        $template = null;
    }
    return xarTplModule('dynamicdata','admin','modifyconfighook',$data,$template);
}

?>