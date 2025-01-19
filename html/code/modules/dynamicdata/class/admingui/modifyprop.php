<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use DataObjectDescriptor;
use DataObjectFactory;
use DataPropertyMaster;
use DataStoreFactory;
use Exception;
use xarController;
use xarMod;
use xarModHooks;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin modifyprop function
 * @extends MethodClass<AdminGui>
 */
class ModifypropMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the dynamic properties for a module + itemtype
     * @param array<string,mixed> $args
     * with
     *     int itemid
     *     int module_id
     *     int itemtype
     *     string table
     *     mixed details
     *     string layout (optional)
     * @return array|string|void data for the template display
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');

        if (!$this->var()->fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('details', 'isset', $details, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('layout', 'str:1', $layout, 'default', xarVar::NOT_REQUIRED)) {
            return;
        }

        $args = DataObjectDescriptor::getObjectID(
            [
                'objectid' => $itemid,
                'moduleid' => $module_id,
                'itemtype' => $itemtype,
            ]
        );
        $objectinfo = DataObjectFactory::getObjectInfo($args);
        $data['objectinfo'] = $objectinfo;
        $object = DataObjectFactory::getObject($args);

        if (!empty($objectinfo)) {
            $objectid = $objectinfo['objectid'];
            $module_id = $objectinfo['moduleid'];
            $itemtype = $objectinfo['itemtype'];
            $label =  $objectinfo['label'];
            // check security of the parent object
            // set context if available in function
            $tmpobject = DataObjectFactory::getObject($objectinfo, $this->getContext());
            if (!$tmpobject->checkAccess('config')) {
                $msg = $this->ml('Configure #(1) is forbidden', $tmpobject->label);
                return $this->ctl()->forbidden($msg);
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
            if (!xarSecurity::check('AdminDynamicData')) {
                return;
            }
            $objectid = null;
            $data['visibility'] = 'public';
        }
        $data['module_id'] = $module_id;
        $data['itemtype'] = $itemtype;

        // Generate a one-time authorisation code for this operation
        $data['authid'] = $this->sec()->genAuthKey();

        $modinfo = xarMod::getInfo($module_id);
        if (!isset($objectinfo)) {
            $data['objectid'] = null;
            if (!empty($itemtype)) {
                $data['label'] = $this->ml('for module #(1) - item type #(2)', $modinfo['displayname'], $itemtype);
            } else {
                $data['label'] = $this->ml('for module #(1)', $modinfo['displayname']);
            }
        } else {
            $data['objectid'] = $objectinfo['objectid'];
            if (!empty($itemtype)) {
                $data['label'] = $this->ml('for #(1)', $objectinfo['label']);
            } else {
                $data['label'] = $this->ml('for #(1)', $objectinfo['label']);
            }
        }
        $data['itemid'] = $data['objectid'];
        xarTpl::setPageTitle($this->ml('Modify DataProperties #(1)', $data['label']));

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
            $data['sources'] = \Xaraya\DataObject\DataStores\DataStoreFactory::getDataSources($object);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return $this->ctl()->notFound($msg);
        }

        $isprimary = 0;
        foreach (array_keys($data['fields']) as $field) {
            // replace newlines with [LF] for textbox
            if (!empty($data['fields'][$field]['defaultvalue']) && preg_match("/\n/", $data['fields'][$field]['defaultvalue'])) {
                // Note : we could use addcslashes here, but that could lead to a whole bunch of other issues...
                $data['fields'][$field]['defaultvalue'] = preg_replace("/\r?\n/", '[LF]', $data['fields'][$field]['defaultvalue']);
            }
            if (DataPropertyMaster::isPrimaryType($data['fields'][$field]['type'])) { // item id
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

        $data['statictitle'] = $this->ml('Static Properties (guessed from module table definitions for now)');

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

        $data['relationstitle'] = $this->ml('Relationships with other Modules/Properties (only item display hooks for now)');
        $data['labels']['module'] = $this->ml('Module');
        $data['labels']['linktype'] = $this->ml('Link Type');
        $data['labels']['linkfrom'] = $this->ml('From');
        $data['labels']['linkto'] = $this->ml('To');

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
}
