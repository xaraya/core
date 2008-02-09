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
 * Update the dynamic properties for a module + itemtype
 *
 * @param int objectid
 * @param int modid
 * @param int itemtype
 * @throws BAD_PARAM
 * @return bool true on success and redirect to modifyprop
 */
function dynamicdata_admin_updateprop()
{
    if(!xarVarFetch('objectid',          'isset', $objectid,          NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',             'isset', $modid,             NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',          'int:1:', $itemtype,         0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',             'isset', $table,             NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_label',          'isset', $dd_label,          NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_type',           'isset', $dd_type,           NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_default',        'isset', $dd_defaultvalue,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_source',         'isset', $dd_source,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('display_dd_status', 'isset', $display_dd_status, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('input_dd_status',   'isset', $input_dd_status,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_configuration',     'isset', $dd_configuration,     NULL, XARVAR_DONT_SET)) {return;}

    if (!xarSecConfirmAuthKey()) return;

    $objectinfo = DataObjectMaster::getObjectInfo(
                                    array(
                                    'objectid' => $objectid,
                                    'moduleid' => $modid,
                                    'itemtype' => $itemtype,
                                    ));

    if (isset($objectinfo)) {
        $objectid = $objectinfo['objectid'];
        $modid = $objectinfo['moduleid'];
        $itemtype = $objectinfo['itemtype'];
    } elseif (!empty($modid)) {
        $modinfo = xarModGetInfo($modid);
        if (!empty($modinfo['name'])) {
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                      array('moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        }
    }

    if (empty($modid)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module id', 'admin', 'updateprop', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'allprops' => true));
    $isprimary = 0;

    $i = 0;
    // update old fields
    foreach ($fields as $name => $field) {
        $id = $field['id'];
        $i++;
        if (empty($dd_label[$id])) {
            // delete property (and corresponding data) in xaradminapi.php
            if (!xarModAPIFunc('dynamicdata','admin','deleteprop',
                              array('id' => $id))) {
                return;
            }
        } else {
             // TODO : only if necessary
            // update property in xaradminapi.php
            if (!isset($dd_defaultvalue[$id])) {
                $dd_defaultvalue[$id] = null;
            } elseif (!empty($dd_defaultvalue[$id]) && preg_match('/\[LF\]/',$dd_defaultvalue[$id])) {
                // replace [LF] with line-feed again
                $lf = chr(10);
                $dd_defaultvalue[$id] = preg_replace('/\[LF\]/',$lf,$dd_defaultvalue[$id]);
            }
            if (!isset($dd_configuration[$id])) {
                $dd_configuration[$id] = null;
            }
            if (!isset($display_dd_status[$id])) {
                $display_dd_status[$id] = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
            }
            if (!isset($input_dd_status[$id])) {
                $input_dd_status[$id] = DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
            }
            $dd_status[$id] = $display_dd_status[$id] + $input_dd_status[$id];
            if (!xarModAPIFunc('dynamicdata','admin','updateprop',
                              array('id' => $id,
                              //      'modid' => $modid,
                              //      'itemtype' => $itemtype,
                                    'label' => $dd_label[$id],
                                    'type' => $dd_type[$id],
                                    'defaultvalue' => $dd_defaultvalue[$id],
                              //      'source' => $dd_source[$id],
                                    'status' => $dd_status[$id],
                                    'configuration' => $dd_configuration[$id]))) {
                return;
            }
            if ($dd_type[$id] == 21) { // item id
                $isprimary = 1;
            }
        }
    }
    $i++;
    // insert new field
    if (!empty($dd_label[0]) && !empty($dd_type[0])) {
        // create new property in xaradminapi.php
        $name = strtolower($dd_label[0]);
        $name = preg_replace('/[^a-z0-9_]+/','_',$name);
        $name = preg_replace('/_$/','',$name);
        if (!isset($display_dd_status[0])) {
            $display_dd_status[0] = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        }
        if (!isset($input_dd_status[0])) {
            $input_dd_status[0] = DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
        }
        $dd_status[0] = $display_dd_status[0] + $input_dd_status[0];
        $id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                array('name' => $name,
                                      'label' => $dd_label[0],
                                      'objectid' => $objectid,
                                      'moduleid' => $modid,
                                      'itemtype' => $itemtype,
                                      'type' => $dd_type[0],
                                      'defaultvalue' => $dd_defaultvalue[0],
                                      'source' => $dd_source[0],
                                      'status' => $dd_status[0],
                                      'seq' => $i,
                                      'configuration' => $dd_configuration[0]));
        if (empty($id)) {
            return;
        }
        if ($dd_type[0] == 21) { // item id
            $isprimary = 1;
        }
    }

    if ($isprimary) {
        $modinfo = xarModGetInfo($modid);
        xarModCallHooks('module','updateconfig',$modinfo['name'],
                        array('module' => $modinfo['name'],
                              'itemtype' => $itemtype));
    }

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                        array('itemid'    => $objectid,
                              'table'    => $table)));
}
?>
