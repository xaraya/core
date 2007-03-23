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
 * Modify the dynamic properties for a module + itemtype
 *
 * @param int itemid
 * @param int modid
 * @param int itemtype
 * @param table
 * @param details
 * @param string layout (optional)
 * @throws BAD_PARAM
 * @return array with $data
 */
function dynamicdata_admin_modifyprop()
{
    $data = xarModAPIFunc('dynamicdata','admin','menu');

    if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('itemid',   'isset', $itemid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('details',  'isset', $details,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1', $layout,   'default', XARVAR_NOT_REQUIRED)) {return;}

    $objectinfo = DataObjectMaster::getObjectInfo(
                                    array('objectid' => $itemid));

    if (isset($objectinfo)) {
        $objectid = $objectinfo['objectid'];
        $modid = $objectinfo['moduleid'];
        $itemtype = $objectinfo['itemtype'];
        $label =  $objectinfo['label'];
    }
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    $modinfo = xarModGetInfo($modid);
    if (!isset($object)) {
        $data['objectid'] = 0;
        if (!empty($itemtype)) {
            $data['label'] = xarML('for module #(1) - item type #(2)', $modinfo['displayname'], $itemtype);
        } else {
            $data['label'] = xarML('for module #(1)', $modinfo['displayname']);
        }
    } else {
        $data['objectid'] = $object['objectid'];
        if (!empty($itemtype)) {
            $data['label'] = xarML('for #(1)', $object['label']);
        } else {
            $data['label'] = xarML('for #(1)', $object['label']);
        }
    }

    $data['fields'] = xarModAPIFunc('dynamicdata','user','getprop',
                                   array('objectid' => $itemid,
                                         'allprops' => true));
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    // get possible data sources (with optional extra table)
// TODO: combine with static tables list below someday ?
    $params = array();
    if (!empty($table)) {
        $params['table'] = $table;
        $data['table'] = $table;
    } else {
        $data['table'] = null;
    }
    $data['sources'] = DataStoreFactory::getDataSources($params);
    if (empty($data['sources'])) {
        $data['sources'] = array();
    }

    $isprimary = 0;
    foreach (array_keys($data['fields']) as $field) {
        // replace newlines with [LF] for textbox
        if (!empty($data['fields'][$field]['default']) && preg_match("/\n/",$data['fields'][$field]['default'])) {
            // Note : we could use addcslashes here, but that could lead to a whole bunch of other issues...
            $data['fields'][$field]['default'] = preg_replace("/\r?\n/",'[LF]',$data['fields'][$field]['default']);
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

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'name' => xarML('Name'),
                            'label' => xarML('Label'),
                            'type' => xarML('Property Type'),
                            'default' => xarML('Default'),
                            'source' => xarML('Data Source'),
                            'status' => xarML('Status'),
                            'validation' => xarML('Validation'),
                            'new' => xarML('New'),
                      );

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
                                             array('modid' => $modid,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype,
                                                   'details' => 1));
        }
        return $data;
    }

    $data['details'] = $details;

// TODO: allow modules to specify their own properties
    // (try to) show the "static" properties, corresponding to fields in dedicated
    // tables for this module
    $data['static'] = xarModAPIFunc('dynamicdata','util','getstatic',
                                   array('modid' => $modid,
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
    $data['relations'] = xarModAPIFunc('dynamicdata','util','getrelations',
                                       array('modid' => $modid,
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
                                         array('modid' => $modid,
                                               'itemtype' => empty($itemtype) ? null : $itemtype));
    }

    // Return the template variables defined in this function
    return $data;
}

?>
