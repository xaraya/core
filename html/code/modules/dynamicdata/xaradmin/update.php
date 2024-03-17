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
 * Update current item
 *
 * This is a standard function that is called with the results of the
 * form supplied by xarMod::guiFunc('dynamicdata','admin','modify') to update a current item
 *
 * @param array<string, mixed> $args
 * with
 *     int    objectid
 *     int    module_id
 *     int    itemtype
 *     int    itemid
 *     string return_url
 *     bool   preview
 *     string join
 *     string table
 */
function dynamicdata_admin_update(array $args = [], $context = null)
{
    extract($args);
    $data ??= [];

    if(!xarVar::fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('tplmodule', 'isset', $tplmodule, 'dynamicdata', xarVar::NOT_REQUIRED)) {
        return;
    }
    if(!xarVar::fetch('return_url', 'isset', $return_url, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('preview', 'isset', $preview, 0, xarVar::NOT_REQUIRED)) {
        return;
    }

    if (!xarVar::fetch('tab', 'pre:trim:lower:str:1', $data['tab'], 'edit', xarVar::NOT_REQUIRED)) {
        return;
    }

    // Security
    if(!xarSecurity::check('EditDynamicData')) {
        return;
    }

    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'bad_author']);
    }

    // set context if available in function
    $myobject = DataObjectFactory::getObject(
        ['objectid' => $objectid,
        'join'     => $join,
        'table'    => $table,
        'itemid'   => $itemid],
        $context
    );

    $itemid = $myobject->getItem();

    switch ($data['tab']) {

        case 'edit':

            // if we're editing a dynamic property, save its property type to cache
            // for correct processing of the configuration rule (ValidationProperty)
            if ($myobject->objectid == 2) {
                xarVar::setCached('dynamicdata', 'currentproptype', $myobject->properties['type']);
            }

            $isvalid = $myobject->checkInput([], 0, 'dd');

            // recover any session var information
            $data = xarMod::apiFunc('dynamicdata', 'user', 'getcontext', ['module' => $tplmodule]);
            extract($data);

            if (!empty($preview) || !$isvalid) {
                $data = array_merge($data, xarMod::apiFunc('dynamicdata', 'admin', 'menu'));
                $data['object'] = & $myobject;

                $data['objectid'] = $myobject->objectid;
                $data['itemid'] = $itemid;
                $data['authid'] = xarSec::genAuthKey();
                $data['preview'] = $preview;
                //        $data['tplmodule'] = $tplmodule;
                if (!empty($return_url)) {
                    $data['return_url'] = $return_url;
                }

                // Makes this hooks call explictly from DD - why ???
                ////$modinfo = xarMod::getInfo($myobject->moduleid);
                //$modinfo = xarMod::getInfo(182);
                $myobject->callHooks('modify');
                $data['hooks'] = $myobject->hookoutput;

                if ($myobject->objectid == 1) {
                    $data['label'] = $myobject->properties['label']->value;
                    xarTpl::setPageTitle(xarML('Modify DataObject #(1)', $data['label']));
                } else {
                    $data['label'] = $myobject->label;
                    xarTpl::setPageTitle(xarML('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
                }
                return xarTpl::module($tplmodule, 'admin', 'modify', $data);
            }

            // Valid and not previewing, update the object
            $itemid = $myobject->updateItem();
            if (!isset($itemid)) {
                return;
            } // throw back

            // If we are here then the update is valid: reset the session var
            xarSession::setVar('ddcontext.' . $tplmodule, ['tplmodule' => $tplmodule]);

            // special case for dynamic objects themselves
            if ($myobject->objectid == 1) {
                // check if we need to set a module alias (or remove it) for short URLs
                $name = $myobject->properties['name']->value;
                $alias = xarModAlias::resolve($name);
                $isalias = $myobject->properties['isalias']->value;
                if (!empty($isalias)) {
                    // no alias defined yet, so we create one
                    if ($alias == $name) {
                        $args = ['modName' => 'dynamicdata', 'aliasModName' => $name];
                        xarMod::apiFunc('modules', 'admin', 'add_module_alias', $args);
                    }
                } else {
                    // this was a defined alias, so we remove it
                    if ($alias == 'dynamicdata') {
                        $args = ['modName' => 'dynamicdata', 'aliasModName' => $name];
                        xarMod::apiFunc('modules', 'admin', 'delete_module_alias', $args);
                    }
                }

            }

            break;

        case 'clone':
            // only admins can change access rules
            $adminaccess = xarSecurity::check('', 0, 'All', $myobject->objectid . ":" . $myobject->name . ":" . "All", '', '', 0, 800);

            if (!$adminaccess) {
                return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'no_privileges']);
            }

            $name = $myobject->properties['name']->getValue();
            $myobject->properties['name']->setValue();
            if(!xarVar::fetch('newname', 'str', $newname, "", xarVar::NOT_REQUIRED)) {
                return;
            }
            if (empty($newname)) {
                $newname = $name . "_copy";
            }
            $newname = strtolower(str_ireplace(" ", "_", $newname));

            // Check if this object already exists
            try {
                $testobject = DataObjectFactory::getObject(['name' => $newname]);
            } catch (Exception $e) {
                return xarTpl::module('dynamicdata', 'user', 'errors', ['layout' => 'duplicate_name', 'name' => $newname]);
            }

            $itemtype = $myobject->getNextItemtype(['moduleid' => $myobject->properties['module_id']->getValue()]);
            $myobject->properties['name']->setValue($newname);
            $myobject->properties['label']->setValue(ucfirst($newname));
            $myobject->properties['itemtype']->setValue($itemtype);
            $newitemid = $myobject->createItem(['itemid' => 0]);

            $oldobject = DataObjectFactory::getObject(['objectid' => $itemid]);
            foreach ($oldobject->properties as $property) {
                $fields['name'] = $property->name;
                $fields['label'] = $property->label;
                $fields['objectid'] = $newitemid;
                $fields['type'] = $property->type;
                $fields['defaultvalue'] = $property->defaultvalue;
                $fields['source'] = $property->source;
                $fields['status'] = $property->status;
                $fields['seq'] = $property->seq;
                $fields['configuration'] = $property->configuration;
                xarMod::apiFunc('dynamicdata', 'admin', 'createproperty', $fields);
            }

            // Got to the object to modify it
            xarController::redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'modify',
                ['itemid' => $newitemid]
            ));
            return true;
    }

    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } elseif ($myobject->objectid == 1) { // for dynamic objects, return to modify
        xarController::redirect(xarController::URL(
            'dynamicdata',
            'admin',
            'modify',
            ['itemid' => $itemid]
        ));
    } elseif ($myobject->objectid == 2) { // for dynamic properties, return to modifyprop
        $objectid = $myobject->properties['objectid']->value;
        xarController::redirect(xarController::URL(
            'dynamicdata',
            'admin',
            'modifyprop',
            ['itemid' => $objectid]
        ));
    } elseif (!empty($table)) {
        xarController::redirect(xarController::URL(
            'dynamicdata',
            'admin',
            'view',
            ['table' => $table]
        ));
    } else {
        xarController::redirect(xarController::URL(
            'dynamicdata',
            'admin',
            'view',
            ['itemid' => $objectid,
            'tplmodule' => $tplmodule]
        ));
    }
    return true;
}
