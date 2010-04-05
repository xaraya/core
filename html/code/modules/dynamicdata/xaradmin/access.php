<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
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
 * @return string
 */
function dynamicdata_admin_access($args)
{
    extract($args);

    if(!xarVarFetch('itemid',   'isset', $itemid)) {return;}
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

    // user needs admin access to changethe access rules
    $data['adminaccess'] = xarSecurityCheck('',0,'All',$object->objectid . ":" . $name . ":" . "$itemid",0,'',0,800);

    // gotta be an admin to access dataobject access settings
    if (!$data['adminaccess'])
        return xarTplModule('privileges','user','errors',array('layout' => 'no_privileges'));

    // Get the object's configuration
    if (!empty($object->properties['config'])) {
        $configuration = unserialize($object->properties['config']->value);
    } else {
        $configuration = array();
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
            return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
        }

        // Get the access information from the template
/*
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        foreach ($data['levels'] as $level => $info) {
            $isvalid = $accessproperty->checkInput($object->name . '_' . $level);
            $configuration['access'][$level] = $accessproperty->value;
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
            $configuration['access'] = serialize($accesslist);
        } else {
            // clear the access list first
            unset($configuration['access']);
        }
        // then serialize the configuration for update
        $configstring = serialize($configuration);
        $itemid = $object->updateItem(array('config' => $configstring));

        if(!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
        if (!empty($return_url)) {
            xarResponse::redirect($return_url);
        } else {
            xarResponse::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                            array('tplmodule' => $tplmodule)));
        }
        return true;
    }

    if (!empty($configuration['access'])) {
        // unserialize the access list
        try{
            $data['access'] = unserialize($configuration['access']);
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

    $data['authid'] = xarSecGenAuthKey();

    if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-access.xt') ||
        file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-access-' . $data['template'] . '.xt')) {
        return xarTplModule($data['tplmodule'],'admin','access',$data,$data['template']);
    } else {
        return xarTplModule('dynamicdata','admin','access',$data,$data['template']);
    }
}

?>
