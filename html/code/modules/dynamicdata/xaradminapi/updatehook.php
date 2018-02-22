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
 * update fields for an item - hook for ('item','update','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['objectid'] ID of the object<br/>
 *        string   $args['extrainfo'] extra information
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_updatehook(Array $args=array())
{
    $verbose = false;

    extract($args);

    if (!isset($dd_function) || $dd_function != 'createhook') {
        $dd_function = 'updatehook';
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4) (not numeric or not set)';
        $vars = array('object id', 'admin', $dd_function, 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('extrainfo', 'admin', $dd_function, 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    // We can exit immediately if the status flag is set because we are just updating
    // the status in the articles or other content module that works on that principle
    // Bug 1960 and 3161
    if (xarVarIsCached('Hooks.all','noupdate') || !empty($extrainfo['statusflag'])){
        return $extrainfo;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    // don't allow hooking to yourself in DD
    if ($modname == 'dynamicdata') {
        return $extrainfo;
    }

    $module_id = xarMod::getRegID($modname);
    if (empty($module_id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name', 'admin', $dd_function, 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }
    if (empty($itemid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('item id', 'admin', $dd_function, 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    $args = DataObjectDescriptor::getObjectID(array('moduleid'  => $module_id,
                                       'itemtype'  => $itemtype));
    $myobject = DataObjectMaster::getObject(array('objectid' => $args['objectid'],
                                         'itemid'   => $itemid));

    // If no object returned, bail and pass the extrainfo to the next hook
    if (!isset($myobject)) return $extrainfo;

    $myobject->getItem();

    // use the values passed via $extrainfo if available
    $isvalid = $myobject->checkInput($extrainfo);
    if (!$isvalid) {
        $vars = array();
        if ($verbose) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = array('input', 'admin', $dd_function, 'dynamicdata');
            // Note : we can't use templating here
            $msg .= ' : ';
            $i=5;
            foreach ($myobject->properties as $property) {
                if (!empty($property->invalid)) {
                    $msg .= "#(".$i++.") = invalid #(".$i++.") - ";
                    $vars[]=$property->label;
                    $vars[]=$property->invalid;
                }
            }
        } else {
            $msg = '';
            foreach ($myobject->properties as $property) {
                if (!empty($property->invalid)) {
                    $msg .= $property->invalid . ' ';
                }
            }
        }
        throw new BadParameterException($vars, $msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        // CHECKME: not anymore now, exceptions are either fatal or caught, in this case, we probably want to catch it in the callee.
        //return $extrainfo;
    }

    if ($dd_function == 'createhook') {
        $itemid = $myobject->createItem();
    } else {
        $itemid = $myobject->updateItem();
    }

    if (empty($itemid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('create/update', 'admin', $dd_function, 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }
    // Return the extra info
    return $extrainfo;
}
?>
