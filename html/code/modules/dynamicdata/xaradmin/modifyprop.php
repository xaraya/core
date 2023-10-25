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

sys::import('xaraya.datastores.factory');
use Xaraya\DataObject\DataStores\DataStoreFactory;

/**
 * Modify the dynamic properties for a module + itemtype
 *
 * @param array<string, mixed> $args
 * with
 *     int itemid
 *     int module_id
 *     int itemtype
 *     string table
 *     mixed details
 *     string layout (optional)
 * @return array<mixed>|string|void data for the template display
 */
function dynamicdata_admin_modifyprop(array $args = [])
{
    extract($args);
    $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

    if(!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('details', 'isset', $details, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('layout', 'str:1', $layout, 'default', xarVar::NOT_REQUIRED)) {
        return;
    }

    $args = DataObjectDescriptor::getObjectID(
        [
            'objectid' => $itemid,
            'moduleid' => $module_id,
            'itemtype' => $itemtype,
        ]
    );
    $objectinfo = DataObjectMaster::getObjectInfo($args);
    $data['objectinfo'] = $objectinfo;
    $object = DataObjectMaster::getObject($args);

    if (!empty($objectinfo)) {
        $objectid = $objectinfo['objectid'];
        $module_id = $objectinfo['moduleid'];
        $itemtype = $objectinfo['itemtype'];
        $label =  $objectinfo['label'];
        // check security of the parent object
        $tmpobject = DataObjectMaster::getObject($objectinfo);
        if (!$tmpobject->checkAccess('config')) {
            return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
        }
        if ($objectid <= 3) {
            // always mark the internal DD objects as 'private' (= items 1-3 in xar_dynamic_objects, see xarinit.php)
            $data['visibility'] = 'private';
        } else {
            // CHECKME: do we always need to load the object class to get its visibility ?
            $data['visibility'] = $tmpobject->visibility;
        }
        unset($tmpobject);
    } else {
        // Security
        if(!xarSecurity::check('AdminDynamicData')) {
            return;
        }
        $objectid = null;
        $data['visibility'] = 'public';
    }
    $data['module_id'] = $module_id;
    $data['itemtype'] = $itemtype;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSec::genAuthKey();

    $modinfo = xarMod::getInfo($module_id);
    if (!isset($objectinfo)) {
        $data['objectid'] = null;
        if (!empty($itemtype)) {
            $data['label'] = xarML('for module #(1) - item type #(2)', $modinfo['displayname'], $itemtype);
        } else {
            $data['label'] = xarML('for module #(1)', $modinfo['displayname']);
        }
    } else {
        $data['objectid'] = $objectinfo['objectid'];
        if (!empty($itemtype)) {
            $data['label'] = xarML('for #(1)', $objectinfo['label']);
        } else {
            $data['label'] = xarML('for #(1)', $objectinfo['label']);
        }
    }
    $data['itemid'] = $data['objectid'];
    xarTpl::setPageTitle(xarML('Modify DataProperties #(1)', $data['label']));

    $data['fields'] = xarMod::apiFunc(
        'dynamicdata',
        'user',
        'getprop',
        ['objectid' => $objectid,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'allprops' => true]
    );
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = [];
    }

    try {
        $data['sources'] = DataStoreFactory::getDataSources($object);
    } catch (Exception $e) {
        return xarResponse::NotFound($e->getMessage());
    }

    $isprimary = 0;
    foreach (array_keys($data['fields']) as $field) {
        // replace newlines with [LF] for textbox
        if (!empty($data['fields'][$field]['defaultvalue']) && preg_match("/\n/", $data['fields'][$field]['defaultvalue'])) {
            // Note : we could use addcslashes here, but that could lead to a whole bunch of other issues...
            $data['fields'][$field]['defaultvalue'] = preg_replace("/\r?\n/", '[LF]', $data['fields'][$field]['defaultvalue']);
        }
        if ($data['fields'][$field]['type'] == 21) { // item id
            $isprimary = 1;
            //    break;
        }
    }
    $hooks = [];
    if ($isprimary) {
        $hooks = xarModHooks::call(
            'module',
            'modifyconfig',
            $modinfo['name'],
            ['module' => $modinfo['name'],
                                       'itemtype' => $itemtype]
        );
    }
    $data['hooks'] = $hooks;

    $data['fieldtypeprop']   = & DataPropertyMaster::getProperty(['type' => 'fieldtype']);
    $data['fieldstatusprop'] = & DataPropertyMaster::getProperty(['type' => 'fieldstatus']);
    $data['dropdown']        = & DataPropertyMaster::getProperty(['type' => 'dropdown']);
    $data['checkbox']        = & DataPropertyMaster::getProperty(['type' => 'checkbox']);

    // We have to specify this here, the js expects non xml urls and the => makes the template invalied
    $data['urlform'] = xarController::URL('dynamicdata', 'admin', 'form', ['objectid' => $data['objectid'], 'theme' => 'print'], false);
    $data['layout'] = $layout;

    if (empty($details)) {
        $data['static'] = [];
        $data['relations'] = [];
        if (!empty($objectid)) {
            $data['detailslink'] = xarController::URL(
                'dynamicdata',
                'admin',
                'modifyprop',
                ['itemid' => $objectid,
                                                   'details' => 1]
            );
        } else {
            $data['detailslink'] = xarController::URL(
                'dynamicdata',
                'admin',
                'modifyprop',
                ['module_id' => $module_id,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype,
                                                   'details' => 1]
            );
        }
        return $data;
    }

    $data['details'] = $details;

    // TODO: allow modules to specify their own properties
    // (try to) show the "static" properties, corresponding to fields in dedicated
    // tables for this module
    $data['static'] = xarMod::apiFunc(
        'dynamicdata',
        'util',
        'getstatic',
        ['module_id' => $module_id,
                                         'itemtype' => $itemtype]
    );
    if (!isset($data['static']) || $data['static'] == false) {
        $data['static'] = [];
        $data['tables'] = [];
    } else {
        $data['tables'] = [];
        foreach ($data['static'] as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tables'][$table] = $table;
            }
        }
    }

    $data['statictitle'] = xarML('Static Properties (guessed from module table definitions for now)');

    // TODO: allow other kinds of relationships than hooks
    // (try to) get the relationships between this module and others
    $data['relations'] = xarMod::apiFunc(
        'dynamicdata',
        'util',
        'getrelations',
        ['module_id' => $module_id,
                                             'itemtype' => $itemtype]
    );
    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = [];
    }

    $data['relationstitle'] = xarML('Relationships with other Modules/Properties (only item display hooks for now)');
    $data['labels']['module'] = xarML('Module');
    $data['labels']['linktype'] = xarML('Link Type');
    $data['labels']['linkfrom'] = xarML('From');
    $data['labels']['linkto'] = xarML('To');

    if (!empty($objectid)) {
        $data['detailslink'] = xarController::URL(
            'dynamicdata',
            'admin',
            'modifyprop',
            ['itemid' => $objectid]
        );
    } else {
        $data['detailslink'] = xarController::URL(
            'dynamicdata',
            'admin',
            'modifyprop',
            ['module_id' => $module_id,
                                               'itemtype' => empty($itemtype) ? null : $itemtype]
        );
    }

    return $data;
}
