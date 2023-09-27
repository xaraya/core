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
 * Modify an item
 *
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 *
 * @param int objectid the id of the item to be modified
 * @param int module_id the id of the module where the item comes from
 * @param int itemtype the id of the itemtype of the item
 * @param string join
 * @param string table
 * @return string|void output display string
 */
function dynamicdata_admin_modify(array $args = [])
{
    extract($args);

    if(!xarVar::fetch('objectid', 'id', $objectid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('notfresh', 'isset', $notfresh, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('tplmodule', 'isset', $tplmodule, null, xarVar::DONT_SET)) {
        return;
    }

    if(!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('template', 'isset', $template, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('preview', 'isset', $preview, null, xarVar::DONT_SET)) {
        return;
    }

    $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');
    if (!xarVar::fetch('tab', 'pre:trim:lower:str:1', $data['tab'], 'edit', xarVar::NOT_REQUIRED)) {
        return;
    }

    if (empty($objectid) && empty($name)) {
        $objectid = 1;
    }
    $object = DataObjectMaster::getObject(['objectid' => $objectid,
                                         'name' => $name,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule]);

    // Security
    if (empty($object)) {
        return xarResponse::NotFound();
    }
    if (empty($itemid)) {
        return xarResponse::NotFound();
    }
    if (!$object->checkAccess('update')) {
        return xarResponse::Forbidden(xarML('Update #(1) is forbidden', $object->label));
    }

    $args = $object->toArray();

    if ($notfresh) {
        $isvalid = $object->checkInput();
    } else {
        $object->getItem();
    }
    $data['object'] = $object;
    $data['itemid'] = $args['itemid'];

    switch ($data['tab']) {

        case 'edit':

            // handle special cases
            if ($object->objectid == 1) {
                // check security of the parent object
                $tmpobject = DataObjectMaster::getObject(['objectid' => $object->itemid]);
                if (empty($tmpobject)) {
                    return xarResponse::NotFound();
                }
                if (!$tmpobject->checkAccess('config')) {
                    return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
                }

                // if we're editing a dynamic object, check its own visibility
                if ($object->itemid > 3) {
                    // CHECKME: do we always need to load the object class to get its visibility ?
                    // override the default visibility and moduleid
                    $object->visibility = $tmpobject->visibility;
                    $object->moduleid = $tmpobject->moduleid;
                }
                unset($tmpobject);

            } elseif ($object->objectid == 2) {
                // check security of the parent object
                $tmpobject = DataObjectMaster::getObject(['objectid' => $object->properties['objectid']->value]);
                if (!$tmpobject->checkAccess('config')) {
                    return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
                }
                unset($tmpobject);

                // if we're editing a dynamic property, save its property type to cache
                // for correct processing of the configuration rule (ValidationProperty)
                xarVar::setCached('dynamicdata', 'currentproptype', $object->properties['type']);
            }

            $data['preview'] = $preview;

            // Makes this hooks call explictly from DD - why ???
            ////$modinfo = xarMod::getInfo($args['moduleid']);
            //$modinfo = xarMod::getInfo(182);
            $object->callHooks('modify');
            $data['hooks'] = $object->hookoutput;

            if ($object->objectid == 1) {
                $data['label'] = $object->properties['label']->value;
                xarTpl::setPageTitle(xarML('Modify DataObject #(1)', $data['label']));
            } else {
                $data['label'] = $object->label;
                xarTpl::setPageTitle(xarML('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
            }

            break;

        case 'clone':
            // user needs admin access to change the access rules
            $data['adminaccess'] = xarSecurity::check('', 0, 'All', $object->objectid . ":" . $name . ":" . "$itemid", 0, '', 0, 800);
            $data['name'] = $object->properties['name']->value;
            if ($object->objectid == 1) {
                $data['label'] = $object->properties['label']->value;
                xarTpl::setPageTitle(xarML('Clone DataObject #(1)', $data['label']));
            } else {
                $data['label'] = $object->label;
                xarTpl::setPageTitle(xarML('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
            }
            break;
    }

    $data['tplmodule'] = $args['tplmodule'];   //TODO: is this needed
    $data['objectid'] = $args['objectid'];
    $data['authid'] = xarSec::genAuthKey();

    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify-' . $args['template'] . '.xt')) {
        return xarTpl::module($args['tplmodule'], 'admin', 'modify', $data, $args['template']);
    } else {
        return xarTpl::module('dynamicdata', 'admin', 'modify', $data, $args['template']);
    }
}
