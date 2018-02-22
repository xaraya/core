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
 * Access control for objects
 *
 * This is a standard function that is called whenever an administrator
 * wishes to modify the access to an object
 *
 * @param int itemid the id of the object to be modified
 * @param join
 * @param table
 * @return string output display string
 */
function dynamicdata_admin_access(Array $args=array())
{
    extract($args);

    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if (empty($itemid)) return xarResponse::notFound();
    if(!xarVarFetch('name',     'isset', $name, 'objects', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('confirm',  'isset', $confirm,   NULL, XARVAR_DONT_SET)) {return;}

    $data = xarMod::apiFunc('dynamicdata','admin','menu');

    $object = DataObjectMaster::getObject(array(
                                         'name' => $name,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));
    $object->getItem();

    $data['object'] = $object;
    $data['tplmodule'] = $object->tplmodule;
    $data['template'] = $object->template;
    $data['itemid'] = $object->itemid;
    $data['label'] = $object->properties['label']->value;
    xarTpl::setPageTitle(xarML('Manage Access Rules for #(1)', $data['label']));

    // check security of the parent object ... or DD Admin as fail-safe here
    $tmpobject = DataObjectMaster::getObject(array('objectid' => $object->itemid));
    
    // Security
    if (!$tmpobject->checkAccess('config') && !xarSecurityCheck('AdminDynamicData',0))
        return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
    unset($tmpobject);

    // Get the object's access rules
    if (!empty($object->properties['access']) && !empty($object->properties['access']->value)) {
        try {
            $objectaccess = unserialize($object->properties['access']->value);
        } catch (Exception $e) {
            $objectaccess = array();
        }
    } else {
        $objectaccess = array();
    }

    // Specify access levels
    $data['levels'] = array(//'view'   => array('label' => 'View',
                            //                   'mask'  => 'ViewDynamicDataItems'),
                            'display' => array('label' => 'Display',
                                               'mask'  => 'ReadDynamicDataItem'),
                            'update'  => array('label' => 'Modify',
                                               'mask'  => 'EditDynamicDataItem'),
                            'create'  => array('label' => 'Create',
                                               'mask'  => 'AddDynamicDataItem'),
                            'delete'  => array('label' => 'Delete',
                                               'mask'  => 'DeleteDynamicDataItem'),
                            'config'  => array('label' => 'Configure',
                                               'mask'  => 'AdminDynamicDataItem'));
    // Get list of groups
    $data['grouplist'] = array();
    $anonid = xarConfigVars::get(null,'Site.User.AnonymousUID');
    $anonrole = xarRoles::get($anonid);
    $data['grouplist'][$anonid] = $anonrole->getName();
    $groups = xarRoles::getgroups();
    foreach ($groups as $group) {
        $data['grouplist'][$group['id']] = $group['name'];
    }

    if (!empty($confirm)) {
        if (!xarSecConfirmAuthKey()) {
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        }

        // Get the access information from the template
/*
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        foreach ($data['levels'] as $level => $info) {
            $isvalid = $accessproperty->checkInput($object->name . '_' . $level);
            $objectaccess['access'][$level] = $accessproperty->value;
        }
*/
        if(!xarVarFetch('do_access', 'isset', $do_access, NULL, XARVAR_DONT_SET)) {return;}

        // define the new access list for each level
        $accesslist = array();
        if (!empty($do_access)) {
            if(!xarVarFetch('access', 'isset', $access, array(), XARVAR_DONT_SET)) {return;}

            foreach ($data['levels'] as $level => $info) {
                if (empty($access[$level])) {
                    continue;
                }
                if (!isset($accesslist[$level])) {
                    $accesslist[$level] = array();
                }
                foreach ($data['grouplist'] as $roleid => $rolename) {
                    if (empty($access[$level][$roleid])) {
                        continue;
                    }
                    // build list of groups that have access at this level
                    array_push($accesslist[$level], $roleid);
                }
            }
            // serialize the access list first
            $objectaccess['access'] = serialize($accesslist);
        } else {
            // clear the access list first
            unset($objectaccess['access']);
        }

        // define the new filter list
        $filterlist = array();
        if(!xarVarFetch('filters', 'isset', $filters, array(), XARVAR_DONT_SET)) {return;}
        foreach ($filters as $filterid => $filterinfo) {
            if (empty($filterinfo['group']) || empty($filterinfo['prop']) || empty($filterinfo['match'])) {
                continue;
            }
            if (!isset($filterlist[$filterinfo['group']])) {
                $filterlist[$filterinfo['group']] = array();
            }
            array_push($filterlist[$filterinfo['group']], array($filterinfo['prop'], $filterinfo['match'], $filterinfo['value']));
        }
        if (!empty($filterlist)) {
            // serialize the filter list first
            $objectaccess['filters'] = serialize($filterlist);
        } else {
            // clear the filter list first
            unset($objectaccess['filters']);
        }

        // then serialize the access rules for update
        $accessstring = serialize($objectaccess);
        $itemid = $object->updateItem(array('access' => $accessstring));

        if(!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
        if (!empty($return_url)) {
            xarController::redirect($return_url);
        } else {
            xarController::redirect(xarModURL('dynamicdata', 'admin', 'access',
                                            array('itemid' => $itemid,
                                                  'tplmodule' => $tplmodule)));
        }
        return true;
    }

    if (!empty($objectaccess['access'])) {
        // unserialize the access list
        try{
            $data['access'] = unserialize($objectaccess['access']);
        } catch (Exception $e) {
            $data['access'] = array();
        }
    } else {
        $data['access'] = array();
    }

    if (empty($data['access'])) {
        $data['do_access'] = 0;

        // Preset the default access rights using privileges
        $instance = $object->properties['module_id']->value.':'.$object->properties['itemtype']->value.':All';
        foreach ($data['levels'] as $level => $info) {
            $data['access'][$level] = array();
            foreach ($data['grouplist'] as $roleid => $rolename) {
                if (xarSecurity::check($info['mask'],0,'Item',$instance,'',$rolename,0,0)) {
                    // build list of groups that have access at this level
                    array_push($data['access'][$level], $roleid);
                }
            }
        }

    } else {
        $data['do_access'] = 1;
    }

    if (!empty($objectaccess['filters'])) {
        // unserialize the filter list
        try{
            $filterlist = unserialize($objectaccess['filters']);
        } catch (Exception $e) {
            $filterlist = array();
        }
    } else {
        $filterlist = array();
    }
    // rearrange filterlist for template
    $data['filters'] = array();
    foreach ($filterlist as $group => $filters) {
        foreach ($filters as $filter) {
            array_push($data['filters'], array('group' => $group,
                                               'prop'  => $filter[0],
                                               'match' => $filter[1],
                                               'value' => xarVarPrepForDisplay($filter[2]),
                                               'level' => ''));
        }
    }
    // add blank filter at the bottom
    array_push($data['filters'], array('group' => '',
                                       'prop'  => '',
                                       'match' => '',
                                       'value' => '',
                                       'level' => ''));

    // get the properties of the current object
    $data['properties'] = DataPropertyMaster::getProperties(array('objectid' => $object->itemid));
    $data['conditions'] = array('eq'    => 'equals',
                                //'start' => 'starts with',
                                //'end'   => 'ends with',
                                //'like'  => 'contains',
                                //'in'    => 'in list a,b,c',
                                'gt'    => 'greater than',
                                'lt'    => 'less than',
                                'ne'    => 'not equal to');

    $data['authid'] = xarSecGenAuthKey();

    if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-access.xt') ||
        file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-access-' . $data['template'] . '.xt')) {
        return xarTpl::module($data['tplmodule'],'admin','access',$data,$data['template']);
    } else {
        return xarTpl::module('dynamicdata','admin','access',$data,$data['template']);
    }
}

?>
