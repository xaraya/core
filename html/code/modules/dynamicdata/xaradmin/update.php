<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Update current item
 *
 * This is a standard function that is called with the results of the
 * form supplied by xarMod::guiFunc('dynamicdata','admin','modify') to update a current item
 *
 * @param int    objectid
 * @param int    module_id
 * @param int    itemtype
 * @param int    itemid
 * @param string return_url
 * @param bool   preview
 * @param string join
 * @param string table
 */
function dynamicdata_admin_update(Array $args=array())
{
    extract($args);

    if(!xarVarFetch('objectid',   'isset', $objectid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'isset', $itemid,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',       'isset', $join,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',      'isset', $table,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule',  'isset', $tplmodule,   'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',    'isset', $preview,     0, XARVAR_NOT_REQUIRED)) {return;}

    if (!xarVarFetch('tab', 'pre:trim:lower:str:1', $data['tab'], 'edit', XARVAR_NOT_REQUIRED)) return;

    // Security
    if(!xarSecurityCheck('EditDynamicData')) return;

    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $itemid = $myobject->getItem();

    switch ($data['tab']) {

        case 'edit':

            // if we're editing a dynamic property, save its property type to cache
            // for correct processing of the configuration rule (ValidationProperty)
            if ($myobject->objectid == 2) {
                xarVarSetCached('dynamicdata','currentproptype', $myobject->properties['type']);
            }

            $isvalid = $myobject->checkInput(array(), 0, 'dd');

            // recover any session var information
            $data = xarMod::apiFunc('dynamicdata','user','getcontext',array('module' => $tplmodule));
            extract($data);

            if (!empty($preview) || !$isvalid) {
                $data = array_merge($data, xarMod::apiFunc('dynamicdata','admin','menu'));
                $data['object'] = & $myobject;

                $data['objectid'] = $myobject->objectid;
                $data['itemid'] = $itemid;
                $data['authid'] = xarSecGenAuthKey();
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
                return xarTpl::module($tplmodule,'admin','modify', $data);
            }

            // Valid and not previewing, update the object

            $itemid = $myobject->updateItem();
            if (!isset($itemid)) return; // throw back

             // If we are here then the update is valid: reset the session var
            xarSession::setVar('ddcontext.' . $tplmodule, array('tplmodule' => $tplmodule));

            // special case for dynamic objects themselves
            if ($myobject->objectid == 1) {
                // check if we need to set a module alias (or remove it) for short URLs
                $name = $myobject->properties['name']->value;
                $alias = xarModGetAlias($name);
                $isalias = $myobject->properties['isalias']->value;
                if (!empty($isalias)) {
                    // no alias defined yet, so we create one
                    if ($alias == $name) {
                        $args = array('modName'=>'dynamicdata', 'aliasModName'=> $name);
                        xarMod::apiFunc('modules', 'admin', 'add_module_alias', $args);
                    }
                } else {
                    // this was a defined alias, so we remove it
                    if ($alias == 'dynamicdata') {
                        $args = array('modName'=>'dynamicdata', 'aliasModName'=> $name);
                        xarMod::apiFunc('modules', 'admin', 'delete_module_alias', $args);
                    }
                }

            }

        break;

        case 'clone':
            // only admins can change access rules
            $adminaccess = xarSecurityCheck('',0,'All',$myobject->objectid . ":" . $myobject->name . ":" . "All",0,'',0,800);

            if (!$adminaccess)
                return xarTpl::module('privileges','user','errors', array('layout' => 'no_privileges'));

            $name = $myobject->properties['name']->getValue();
            $myobject->properties['name']->setValue();
            if(!xarVarFetch('newname',   'str', $newname,   "", XARVAR_NOT_REQUIRED)) {return;}
            if (empty($newname)) $newname = $name . "_copy";
            $newname = strtolower(str_ireplace(" ", "_", $newname));
            
            // Check if this object already exists
            try{
                $testobject = DataObjectMaster::getObject(array('name' => $newname));
                return xarTpl::module('dynamicdata','user','errors', array('layout' => 'duplicate_name', 'newname' => $newname));
            } catch (Exception $e) {}
            
            $itemtype = $myobject->getNextItemtype(array('moduleid' => $myobject->properties['module_id']->getValue()));
            $myobject->properties['name']->setValue($newname);
            $myobject->properties['label']->setValue(ucfirst($newname));
            $myobject->properties['itemtype']->setValue($itemtype);
            $newitemid = $myobject->createItem(array('itemid'=> 0));
            
            $oldobject = DataObjectMaster::getObject(array('objectid' => $itemid));
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
                xarMod::apiFunc('dynamicdata','admin','createproperty',$fields);
            }
            
        break;
    }

    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } elseif ($myobject->objectid == 1) { // for dynamic objects, return to modify
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'modify',
                                      array('itemid' => $itemid)));
    } elseif ($myobject->objectid == 2) { // for dynamic properties, return to modifyprop
        $objectid = $myobject->properties['objectid']->value;
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                      array('itemid' => $objectid)));
    } elseif (!empty($table)) {
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'itemid' => $objectid,
                                      'tplmodule' => $tplmodule
                                      )));
    }
    return true;
}
?>
