<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Modify the dynamic properties for a module + itemtype
 *
 * @param int itemid
 * @param int module_id
 * @param int itemtype
 * @param table
 * @param details
 * @param string layout (optional)
 * @throws BAD_PARAM
 * @return array with $data
 */
function dynamicdata_admin_modifyprop()
{
    $data = xarMod::apiFunc('dynamicdata','admin','menu');

    if(!xarVarFetch('itemid',   'isset', $itemid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',    'isset', $module_id,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('details',  'isset', $details,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1', $layout,   'default', XARVAR_NOT_REQUIRED)) {return;}

    if (!isset($args['itemid']) || (is_null($args['itemid']))) {
        $args = DataObjectDescriptor::getObjectID(
            array(
                'objectid' => $itemid,
                'moduleid' => $module_id,
                'itemtype' => $itemtype,
            )
        );
    }
    $objectinfo = DataObjectMaster::getObjectInfo($args);
    $object = DataObjectMaster::getObject($args);

    if (isset($objectinfo)) {
        $objectid = $objectinfo['objectid'];
        $module_id = $objectinfo['moduleid'];
        $itemtype = $objectinfo['itemtype'];
        $label =  $objectinfo['label'];
        // check security of the parent object
        $tmpobject = DataObjectMaster::getObject($objectinfo);
        if (!$tmpobject->checkAccess('config'))
            return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
        if ($objectid <= 3) {
            // always mark the internal DD objects as 'private' (= items 1-3 in xar_dynamic_objects, see xarinit.php)
            $data['visibility'] = 'private';
        } else {
            // CHECKME: do we always need to load the object class to get its visibility ?
            $data['visibility'] = $tmpobject->visibility;
        }
        unset($tmpobject);
    } else {
        if(!xarSecurityCheck('AdminDynamicData')) return;
        $objectid = null;
        $data['visibility'] = 'public';
    }
    $data['module_id'] = $module_id;
    $data['itemtype'] = $itemtype;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

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
    xarTplSetPageTitle(xarML('Modify DataProperties #(1)', $data['label']));

    $data['fields'] = xarMod::apiFunc('dynamicdata','user','getprop',
                                   array('objectid' => $objectid,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'allprops' => true));
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    $data['sources'] = DataStoreFactory::getDataSources($object->datasources);

    $isprimary = 0;
    foreach (array_keys($data['fields']) as $field) {
        // replace newlines with [LF] for textbox
        if (!empty($data['fields'][$field]['defaultvalue']) && preg_match("/\n/",$data['fields'][$field]['defaultvalue'])) {
            // Note : we could use addcslashes here, but that could lead to a whole bunch of other issues...
            $data['fields'][$field]['defaultvalue'] = preg_replace("/\r?\n/",'[LF]',$data['fields'][$field]['defaultvalue']);
        }
        if ($data['fields'][$field]['type'] == 21) { // item id
            $isprimary = 1;
        //    break;
        }
    }
    $hooks = array();
    if ($isprimary) {
        $hooks = xarModCallHooks('module','modifyconfig',$modinfo['name'],
                                 array('module' => $modinfo['name'],
                                       'itemtype' => $itemtype));
    }
    $data['hooks'] = $hooks;

    $data['fieldtypeprop'] =& DataPropertyMaster::getProperty(array('type' => 'fieldtype'));
    $data['fieldstatusprop'] =& DataPropertyMaster::getProperty(array('type' => 'fieldstatus'));

    // We have to specify this here, the js expects non xml urls and the => makes the template invalied
    $data['urlform'] = xarModURL('dynamicdata','admin','form',array('objectid' => $data['objectid'], 'theme' => 'print'),false);
    $data['layout'] = $layout;

    if (empty($details)) {
        $data['static'] = array();
        $data['relations'] = array();
        if (!empty($objectid)) {
            $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                             array('itemid' => $objectid,
                                                   'details' => 1));
        } else {
            $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                             array('module_id' => $module_id,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype,
                                                   'details' => 1));
        }
        return $data;
    }

    $data['details'] = $details;

// TODO: allow modules to specify their own properties
    // (try to) show the "static" properties, corresponding to fields in dedicated
    // tables for this module
    $data['static'] = xarMod::apiFunc('dynamicdata','util','getstatic',
                                   array('module_id' => $module_id,
                                         'itemtype' => $itemtype));
    if (!isset($data['static']) || $data['static'] == false) {
        $data['static'] = array();
        $data['tables'] = array();
    } else {
        $data['tables'] = array();
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
    $data['relations'] = xarMod::apiFunc('dynamicdata','util','getrelations',
                                       array('module_id' => $module_id,
                                             'itemtype' => $itemtype));
    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = array();
    }

    $data['relationstitle'] = xarML('Relationships with other Modules/Properties (only item display hooks for now)');
    $data['labels']['module'] = xarML('Module');
    $data['labels']['linktype'] = xarML('Link Type');
    $data['labels']['linkfrom'] = xarML('From');
    $data['labels']['linkto'] = xarML('To');

    if (!empty($objectid)) {
        $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                         array('itemid' => $objectid));
    } else {
        $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                         array('module_id' => $module_id,
                                               'itemtype' => empty($itemtype) ? null : $itemtype));
    }

    return $data;
}

?>
