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

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\AdminGui;
use Xaraya\DataObject\AdminApi;
use DataObjectFactory;
use DataPropertyMaster;
use Exception;
use xarConfigVars;
use xarController;
use xarMod;
use xarRoles;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin access function
 * @extends MethodClass<AdminGui>
 */
class AccessMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Access control for objects
     * This is a standard function that is called whenever an administrator
     * wishes to modify the access to an object
     * @param array<string,mixed> $args itemid the id of the object to be modified
     * @return string|true|void output display string
     * @see AdminGui::access()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!$this->var()->check('itemid', $itemid)) {
            return;
        }
        if (empty($itemid)) {
            $msg = $this->ml('Data object not found');
            return $this->ctl()->notFound($msg);
        }
        if (!$this->var()->check('name', $name, 'isset', 'objects')) {
            return;
        }
        if (!$this->var()->check('tplmodule', $tplmodule)) {
            return;
        }
        if (!$this->var()->check('template', $template)) {
            return;
        }
        if (!$this->var()->check('preview', $preview)) {
            return;
        }
        if (!$this->var()->check('confirm', $confirm)) {
            return;
        }

        $data = $adminapi->menu();

        $object = $this->data()->getObject([
            'name' => $name,
            'itemid'   => $itemid,
            'tplmodule' => $tplmodule]);
        $object->getItem();

        $data['object'] = $object;
        $data['tplmodule'] = $object->tplmodule;
        $data['template'] = $object->template;
        $data['itemid'] = $object->itemid;
        $data['label'] = $object->properties['label']->value;
        $this->tpl()->setPageTitle($this->ml('Manage Access Rules for #(1)', $data['label']));

        // check security of the parent object ... or DD Admin as fail-safe here
        // set context if available in function
        $tmpobject = $this->data()->getObject(['objectid' => $object->itemid]);

        // Security
        if (!$tmpobject->checkAccess('config') && !$this->sec()->checkAccess('AdminDynamicData', 0)) {
            $msg = $this->ml('Configure #(1) is forbidden', $tmpobject->label);
            return $this->ctl()->forbidden($msg);
        }
        unset($tmpobject);

        // Get the object's access rules
        if (!empty($object->properties['access']) && !empty($object->properties['access']->value)) {
            try {
                $objectaccess = unserialize($object->properties['access']->value);
            } catch (Exception $e) {
                $objectaccess = [];
            }
        } else {
            $objectaccess = [];
        }

        // Specify access levels
        $data['levels'] = [
            //'view'   => ['label' => 'View',
            //            'mask'  => 'ViewDynamicDataItems'],
            'display' => ['label' => 'Display',
                'mask'  => 'ReadDynamicDataItem'],
            'update'  => ['label' => 'Modify',
                'mask'  => 'EditDynamicDataItem'],
            'create'  => ['label' => 'Create',
                'mask'  => 'AddDynamicDataItem'],
            'delete'  => ['label' => 'Delete',
                'mask'  => 'DeleteDynamicDataItem'],
            'config'  => ['label' => 'Configure',
                'mask'  => 'AdminDynamicDataItem'],
        ];
        // Get list of groups
        $data['grouplist'] = [];
        $anonid = xarConfigVars::get(null, 'Site.User.AnonymousUID');
        $anonrole = xarRoles::get($anonid);
        $data['grouplist'][$anonid] = $anonrole->getName();
        $groups = xarRoles::getgroups();
        foreach ($groups as $group) {
            $data['grouplist'][$group['id']] = $group['name'];
        }

        if (!empty($confirm)) {
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            // Get the access information from the template
            /*
                    $accessproperty = $this->prop()->getProperty(array('name' => 'access'));
                    foreach ($data['levels'] as $level => $info) {
                        $isvalid = $accessproperty->checkInput($object->name . '_' . $level);
                        $objectaccess['access'][$level] = $accessproperty->value;
                    }
            */
            if (!$this->var()->check('do_access', $do_access)) {
                return;
            }

            // define the new access list for each level
            $accesslist = [];
            if (!empty($do_access)) {
                if (!$this->var()->check('access', $access, 'isset', [])) {
                    return;
                }

                foreach ($data['levels'] as $level => $info) {
                    if (empty($access[$level])) {
                        continue;
                    }
                    if (!isset($accesslist[$level])) {
                        $accesslist[$level] = [];
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
            $filterlist = [];
            if (!$this->var()->check('filters', $filters, 'isset', [])) {
                return;
            }
            foreach ($filters as $filterid => $filterinfo) {
                if (empty($filterinfo['group']) || empty($filterinfo['prop']) || empty($filterinfo['match'])) {
                    continue;
                }
                if (!isset($filterlist[$filterinfo['group']])) {
                    $filterlist[$filterinfo['group']] = [];
                }
                array_push($filterlist[$filterinfo['group']], [$filterinfo['prop'], $filterinfo['match'], $filterinfo['value']]);
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
            $itemid = $object->updateItem(['access' => $accessstring]);

            if (!$this->var()->check('return_url', $return_url)) {
                return;
            }
            if (!empty($return_url)) {
                $this->ctl()->redirect($return_url);
            } else {
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'access',
                    ['itemid' => $itemid,
                        'tplmodule' => $tplmodule]
                ));
            }
            return true;
        }

        if (!empty($objectaccess['access'])) {
            // unserialize the access list
            try {
                $data['access'] = unserialize($objectaccess['access']);
            } catch (Exception $e) {
                $data['access'] = [];
            }
        } else {
            $data['access'] = [];
        }

        if (empty($data['access'])) {
            $data['do_access'] = 0;

            // Preset the default access rights using privileges
            $instance = $object->properties['module_id']->value . ':' . $object->properties['itemtype']->value . ':All';
            foreach ($data['levels'] as $level => $info) {
                $data['access'][$level] = [];
                foreach ($data['grouplist'] as $roleid => $rolename) {
                    if (xarSecurity::check($info['mask'], 0, 'Item', $instance, '', $rolename, 0, 0)) {
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
            try {
                $filterlist = unserialize($objectaccess['filters']);
            } catch (Exception $e) {
                $filterlist = [];
            }
        } else {
            $filterlist = [];
        }
        // rearrange filterlist for template
        $data['filters'] = [];
        foreach ($filterlist as $group => $filters) {
            foreach ($filters as $filter) {
                array_push($data['filters'], [
                    'group' => $group,
                    'prop'  => $filter[0],
                    'match' => $filter[1],
                    'value' => $this->var()->prep($filter[2]),
                    'level' => '',
                ]);
            }
        }
        // add blank filter at the bottom
        array_push($data['filters'], [
            'group' => '',
            'prop'  => '',
            'match' => '',
            'value' => '',
            'level' => '',
        ]);

        // get the properties of the current object
        $data['properties'] = $this->prop()->getProperties(['objectid' => $object->itemid]);
        $data['conditions'] = [
            'eq'    => 'equals',
            //'start' => 'starts with',
            //'end'   => 'ends with',
            //'like'  => 'contains',
            //'in'    => 'in list a,b,c',
            'gt'    => 'greater than',
            'lt'    => 'less than',
            'ne'    => 'not equal to',
        ];

        $data['authid'] = $this->sec()->genAuthKey();

        if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-access.xt') ||
            file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-access-' . $data['template'] . '.xt')) {
            return $this->tpl()->module($data['tplmodule'], 'admin', 'access', $data, $data['template']);
        } else {
            return $this->tpl()->module('dynamicdata', 'admin', 'access', $data, $data['template']);
        }
    }
}
